<?php

namespace App\Services;

use App\Models\UpdateRun;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class ApplicationUpdateService
{
    public function __construct(
        protected VersionManifestService $manifestService,
        protected AppSettingsService $appSettingsService,
    ) {}

    public function status(): array
    {
        $localManifest = $this->manifestService->safeReadLocal();

        $status = [
            'healthy' => false,
            'update_available' => false,
            'can_update' => false,
            'auto_update_enabled' => $this->appSettingsService->boolean('auto_update_enabled'),
            'repository_url' => config('services.nebuliton.github_url'),
            'current_branch' => $localManifest['branch'],
            'branch' => $localManifest['branch'],
            'tracked_changes' => [],
            'changed_files' => [],
            'blocked_files' => [],
            'update_paths' => $localManifest['update_paths'],
            'local' => [
                'version' => $localManifest['version'],
                'commit' => null,
            ],
            'remote' => [
                'version' => null,
                'commit' => null,
            ],
            'error' => null,
        ];

        try {
            $localManifest = $this->manifestService->readLocal();
            $branch = $localManifest['branch'];

            $repositoryUrl = $this->runCommand(['git', 'remote', 'get-url', 'origin']);
            $currentBranch = $this->runCommand(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
            $localCommit = $this->runCommand(['git', 'rev-parse', 'HEAD']);
            $trackedChanges = $this->runLines(['git', 'status', '--porcelain', '--untracked-files=no']);

            $this->runCommand(['git', 'fetch', '--quiet', 'origin', $branch], 180);

            $remoteManifest = $this->manifestService->parseJson(
                $this->runCommand(['git', 'show', "origin/{$branch}:version.json"], 180),
            );

            $remoteCommit = $this->runCommand(['git', 'rev-parse', "origin/{$branch}"]);
            $allChangedFiles = $this->runLines(['git', 'diff', '--name-only', 'HEAD', "origin/{$branch}"], 180);
            $blockedFiles = $this->blockedFiles($allChangedFiles, $remoteManifest['update_paths']);
            $managedChangedFiles = array_values(array_diff($allChangedFiles, $blockedFiles));

            $updateAvailable = version_compare(
                $this->normalizeVersion($remoteManifest['version']),
                $this->normalizeVersion($localManifest['version']),
                '>',
            );

            return [
                'healthy' => true,
                'update_available' => $updateAvailable,
                'can_update' => $updateAvailable && $trackedChanges === [] && $blockedFiles === [],
                'auto_update_enabled' => $this->appSettingsService->boolean('auto_update_enabled'),
                'repository_url' => $repositoryUrl,
                'current_branch' => $currentBranch,
                'branch' => $branch,
                'tracked_changes' => $trackedChanges,
                'changed_files' => $managedChangedFiles,
                'blocked_files' => $blockedFiles,
                'update_paths' => $remoteManifest['update_paths'],
                'local' => [
                    'version' => $localManifest['version'],
                    'commit' => $localCommit,
                ],
                'remote' => [
                    'version' => $remoteManifest['version'],
                    'commit' => $remoteCommit,
                ],
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            $status['error'] = $this->normalizeErrorMessage($exception->getMessage());

            return $status;
        }
    }

    public function run(?int $actorUserId = null, bool $automatic = false, ?callable $output = null): array
    {
        if ($automatic && ! $this->appSettingsService->boolean('auto_update_enabled')) {
            return [
                'status' => 'skipped',
                'message' => 'Auto-Update ist derzeit deaktiviert.',
                'run' => null,
                'status_snapshot' => $this->status(),
            ];
        }

        $lock = Cache::lock('application-update-run', 1800);

        if (! $lock->get()) {
            return [
                'status' => 'busy',
                'message' => 'Es läuft bereits ein anderes Update.',
                'run' => null,
                'status_snapshot' => $this->status(),
            ];
        }

        $run = UpdateRun::query()->create([
            'triggered_by_user_id' => $actorUserId,
            'mode' => $automatic ? 'automatic' : 'manual',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $log = [];

        try {
            $status = $this->status();
            $this->hydrateRunFromStatus($run, $status);

            if (! $status['healthy']) {
                throw new RuntimeException($status['error'] ?? 'Die Update-Prüfung ist fehlgeschlagen.');
            }

            if (! $status['update_available']) {
                $summary = 'Keine neue freigegebene Version gefunden.';

                $this->finishRun($run, 'skipped', $summary, [], $log, $status);

                return [
                    'status' => 'skipped',
                    'message' => $summary,
                    'run' => $this->formatRunDetail($run->fresh(['user'])),
                    'status_snapshot' => $status,
                ];
            }

            if ($status['tracked_changes'] !== []) {
                throw new RuntimeException('Lokale Änderungen in tracked Dateien blockieren das Update.');
            }

            if ($status['blocked_files'] !== []) {
                throw new RuntimeException('Das Release enthält Änderungen außerhalb der freigegebenen Update-Pfade.');
            }

            $branch = (string) $status['branch'];
            $targetVersion = (string) data_get($status, 'remote.version', 'unbekannt');
            $changedFiles = (array) ($status['changed_files'] ?? []);

            $this->appendLog($log, "Zielversion: {$targetVersion}");

            $this->runLoggedCommand(
                $log,
                'Übernehme Änderungen per git pull',
                ['git', 'pull', '--ff-only', 'origin', $branch],
                $output,
                300,
            );

            if ($this->shouldRunComposerInstall($changedFiles)) {
                $this->runLoggedCommand(
                    $log,
                    'Installiere Composer-Abhängigkeiten',
                    ['composer', 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'],
                    $output,
                    1800,
                );
            }

            if ($this->shouldRunNpmInstall($changedFiles)) {
                $this->runLoggedCommand(
                    $log,
                    'Installiere Node-Abhängigkeiten',
                    ['npm', 'install'],
                    $output,
                    1800,
                );
            }

            $this->runLoggedCommand(
                $log,
                'Führe Deploy-Skript aus',
                $this->deployCommand(),
                $output,
                1800,
            );

            $finalStatus = $this->status();
            $summary = 'Update auf Version '.data_get($finalStatus, 'local.version', $targetVersion).' abgeschlossen.';

            $this->finishRun($run, 'succeeded', $summary, $changedFiles, $log, $finalStatus);

            return [
                'status' => 'succeeded',
                'message' => $summary,
                'run' => $this->formatRunDetail($run->fresh(['user'])),
                'status_snapshot' => $finalStatus,
            ];
        } catch (\Throwable $exception) {
            $summary = $this->normalizeErrorMessage($exception->getMessage());

            $this->appendLog($log, "FEHLER: {$summary}");
            $this->finishRun($run, 'failed', $summary, [], $log, $this->status());

            return [
                'status' => 'failed',
                'message' => $summary,
                'run' => $this->formatRunDetail($run->fresh(['user'])),
                'status_snapshot' => $this->status(),
            ];
        } finally {
            $lock->release();
        }
    }

    public function runDetail(int $runId): ?array
    {
        try {
            $run = UpdateRun::query()
                ->with('user')
                ->find($runId);
        } catch (\Throwable) {
            return null;
        }

        return $run ? $this->formatRunDetail($run) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentRuns(int $limit = 8): array
    {
        try {
            return UpdateRun::query()
                ->with('user')
                ->latest('started_at')
                ->limit($limit)
                ->get()
                ->map(fn (UpdateRun $run): array => $this->formatRunDetail($run))
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<int, string>  $changedFiles
     * @param  array<int, string>  $allowedPaths
     * @return array<int, string>
     */
    protected function blockedFiles(array $changedFiles, array $allowedPaths): array
    {
        return array_values(array_filter(
            $changedFiles,
            fn (string $path): bool => ! $this->manifestService->isPathAllowed($path, $allowedPaths),
        ));
    }

    /**
     * @param  array<int, string>  $changedFiles
     */
    protected function shouldRunComposerInstall(array $changedFiles): bool
    {
        return $this->containsAny($changedFiles, ['composer.json', 'composer.lock']);
    }

    /**
     * @param  array<int, string>  $changedFiles
     */
    protected function shouldRunNpmInstall(array $changedFiles): bool
    {
        return $this->containsAny($changedFiles, ['package.json', 'package-lock.json']);
    }

    /**
     * @param  array<int, string>  $changedFiles
     * @param  array<int, string>  $needles
     */
    protected function containsAny(array $changedFiles, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $changedFiles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $log
     * @param  array<int, string>  $changedFiles
     * @param  array<string, mixed>|null  $status
     */
    protected function finishRun(
        UpdateRun $run,
        string $status,
        string $summary,
        array $changedFiles,
        array $log,
        ?array $statusSnapshot = null,
    ): void {
        if ($statusSnapshot) {
            $this->hydrateRunFromStatus($run, $statusSnapshot);
        }

        $run->update([
            'status' => $status,
            'summary' => $summary,
            'changed_files' => $changedFiles,
            'log_output' => implode(PHP_EOL, $log),
            'ended_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $status
     */
    protected function hydrateRunFromStatus(UpdateRun $run, array $status): void
    {
        $run->fill([
            'local_version' => data_get($status, 'local.version'),
            'target_version' => data_get($status, 'remote.version'),
            'local_commit' => data_get($status, 'local.commit'),
            'target_commit' => data_get($status, 'remote.commit'),
        ])->save();
    }

    /**
     * @param  array<int, string>  $log
     */
    protected function appendLog(array &$log, string $line): void
    {
        $log[] = '['.now()->format('H:i:s')."] {$line}";
    }

    /**
     * @param  array<int, string>  $log
     */
    protected function runLoggedCommand(
        array &$log,
        string $label,
        array $command,
        ?callable $output = null,
        int $timeout = 180,
    ): void {
        $this->appendLog($log, $label);
        $this->appendLog($log, '$ '.$this->stringifyCommand($command));

        [$stdout, $stderr] = $this->execute($command, $timeout);

        if ($stdout !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $stdout) ?: [] as $line) {
                if ($line === '') {
                    continue;
                }

                $this->appendLog($log, $line);

                if ($output) {
                    $output($line);
                }
            }
        }

        if ($stderr !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $stderr) ?: [] as $line) {
                if ($line === '') {
                    continue;
                }

                $this->appendLog($log, $line);

                if ($output) {
                    $output($line);
                }
            }
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function execute(array $command, int $timeout = 120): array
    {
        $result = Process::path(base_path())
            ->timeout($timeout)
            ->run($command);

        if ($result->failed()) {
            throw new RuntimeException(
                trim($result->errorOutput()) !== ''
                    ? trim($result->errorOutput())
                    : trim($result->output()),
            );
        }

        return [trim($result->output()), trim($result->errorOutput())];
    }

    protected function runCommand(array $command, int $timeout = 120): string
    {
        [$stdout] = $this->execute($command, $timeout);

        return trim($stdout);
    }

    /**
     * @return array<int, string>
     */
    protected function runLines(array $command, int $timeout = 120): array
    {
        $output = $this->runCommand($command, $timeout);

        if ($output === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $output) ?: [])));
    }

    /**
     * @return array<int, string>
     */
    protected function deployCommand(): array
    {
        $mode = (string) config('app_update.deploy_mode', 'local');
        $service = (string) config('app_update.docker_service', 'app');

        if (PHP_OS_FAMILY === 'Windows' && file_exists(base_path('deploy.ps1'))) {
            return [
                'powershell',
                '-ExecutionPolicy',
                'Bypass',
                '-File',
                base_path('deploy.ps1'),
                '-Mode',
                $mode,
                '-Service',
                $service,
                '-Plain',
            ];
        }

        return [
            'bash',
            base_path('deploy.sh'),
            $mode,
            '--service='.$service,
            '--plain',
        ];
    }

    protected function stringifyCommand(array $command): string
    {
        return implode(' ', array_map(function (string $part): string {
            if ($part === '' || preg_match('/\s/', $part)) {
                return '"'.str_replace('"', '\"', $part).'"';
            }

            return $part;
        }, $command));
    }

    protected function normalizeVersion(?string $version): string
    {
        return ltrim((string) $version, 'vV');
    }

    protected function normalizeErrorMessage(string $message): string
    {
        $normalized = trim($message);

        return match (true) {
            str_contains($normalized, 'detected dubious ownership') => 'Git blockiert das Repository wegen abweichender Besitzrechte.',
            str_contains($normalized, '.git/FETCH_HEAD') && str_contains($normalized, 'Permission denied') => 'Git kann FETCH_HEAD nicht schreiben. Prüfe die Rechte im .git-Verzeichnis.',
            str_contains($normalized, '.git/objects') && str_contains($normalized, 'permission') => 'Git kann nicht in .git/objects schreiben. Prüfe die Besitz- und Schreibrechte des Repositorys.',
            str_contains($normalized, 'Permission denied') => 'Dem Laufzeitbenutzer fehlen Dateirechte für das Update oder Deploy.',
            str_contains($normalized, 'No such remote') || str_contains($normalized, 'No remote repository specified') => 'Das Git-Remote "origin" ist nicht korrekt eingerichtet.',
            str_contains($normalized, 'path "version.json" does not exist') || str_contains($normalized, 'exists on disk, but not in') => 'Im Remote-Branch fehlt die Datei version.json.',
            str_contains($normalized, 'not a git repository') => 'Das Projekt ist kein vollständiger Git-Checkout.',
            str_contains($normalized, 'Could not read from remote repository') => 'Das Remote-Repository konnte nicht gelesen werden.',
            str_contains($normalized, 'vite') && str_contains($normalized, 'Permission denied') => 'Der Frontend-Build ist an fehlenden Rechten für Vite gescheitert.',
            str_contains($normalized, 'esbuild') && str_contains($normalized, 'Permission denied') => 'Der Frontend-Build ist an fehlenden Rechten für esbuild gescheitert.',
            str_contains($normalized, 'The process timed out') => 'Ein Update-Schritt hat das Zeitlimit überschritten.',
            default => $normalized !== '' ? $normalized : 'Das Update ist fehlgeschlagen.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatRunDetail(UpdateRun $run): array
    {
        return [
            'id' => $run->id,
            'status' => $run->status,
            'mode' => $run->mode,
            'summary' => $run->summary,
            'local_version' => $run->local_version,
            'target_version' => $run->target_version,
            'local_commit' => $run->local_commit,
            'target_commit' => $run->target_commit,
            'changed_files' => $run->changed_files ?? [],
            'log_output' => $run->log_output,
            'started_at' => $run->started_at,
            'ended_at' => $run->ended_at,
            'triggered_by' => $run->user?->name,
            'created_at' => $run->created_at,
        ];
    }
}
