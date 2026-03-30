<x-filament-panels::page>
    @php
        $status = $this->status;
        $runs = $this->runs;
        $selectedRun = $this->selectedRun;
        $localVersion = data_get($status, 'local.version', 'unbekannt');
        $remoteVersion = data_get($status, 'remote.version', 'unbekannt');
        $repositoryUrl = data_get($status, 'repository_url', config('services.nebuliton.github_url'));
        $branch = data_get($status, 'branch', 'main');
        $currentBranch = data_get($status, 'current_branch', 'main');
        $trackedChanges = data_get($status, 'tracked_changes', []);
        $changedFiles = data_get($status, 'changed_files', []);
        $blockedFiles = data_get($status, 'blocked_files', []);
        $updatePaths = data_get($status, 'update_paths', []);
    @endphp

    <div wire:poll.30s.visible="refreshData" class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span @class([
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' => ($status['healthy'] ?? false),
                            'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' => ! ($status['healthy'] ?? false),
                        ])>
                            {{ ($status['healthy'] ?? false) ? 'Repository gesund' : 'Prüfung fehlgeschlagen' }}
                        </span>

                        <span @class([
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => ($status['update_available'] ?? false),
                            'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300' => ! ($status['update_available'] ?? false),
                        ])>
                            {{ ($status['update_available'] ?? false) ? 'Update verfügbar' : 'Stand aktuell' }}
                        </span>

                        <span @class([
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300' => $this->autoUpdateEnabled,
                            'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300' => ! $this->autoUpdateEnabled,
                        ])>
                            Auto-Update {{ $this->autoUpdateEnabled ? 'aktiv' : 'aus' }}
                        </span>
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Release-Status</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Das System prüft Version, Branch, freigegebene Update-Pfade und blockierende lokale Änderungen.
                        </p>
                    </div>

                    @if (filled($status['error'] ?? null))
                        <div class="rounded-2xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700 dark:border-danger-500/20 dark:bg-danger-500/10 dark:text-danger-200">
                            {{ $status['error'] }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-filament::button color="gray" wire:click="refreshData">
                        Status aktualisieren
                    </x-filament::button>

                    <x-filament::button
                        color="{{ $this->autoUpdateEnabled ? 'warning' : 'gray' }}"
                        wire:click="toggleAutoUpdate"
                    >
                        {{ $this->autoUpdateEnabled ? 'Auto-Update deaktivieren' : 'Auto-Update aktivieren' }}
                    </x-filament::button>

                    <x-filament::button
                        color="primary"
                        wire:click="runUpdate"
                        :disabled="! ($status['can_update'] ?? false)"
                    >
                        Update jetzt ausführen
                    </x-filament::button>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400">Installiert</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $localVersion }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $currentBranch }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400">Remote</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $remoteVersion }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $branch }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400">Repository</p>
                    <a href="{{ $repositoryUrl }}" target="_blank" rel="noreferrer" class="mt-2 block text-base font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-300">
                        {{ \Illuminate\Support\Str::of($repositoryUrl)->replace(['https://', 'http://'], '') }}
                    </a>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Origin-Remote</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400">Update-Status</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                        {{ ($status['can_update'] ?? false) ? 'Bereit' : 'Gesperrt' }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ count($blockedFiles) }} blockiert, {{ count($trackedChanges) }} lokal geändert
                    </p>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <x-filament::section heading="Sicherheitsprüfung" description="Nur freigegebene Projektbereiche dürfen automatisch überschrieben werden.">
                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Lokale Änderungen</h3>
                            <span class="text-xs text-gray-400">{{ count($trackedChanges) }}</span>
                        </div>

                        <div class="mt-4 space-y-2">
                            @forelse ($trackedChanges as $file)
                                <div class="rounded-xl bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700 dark:bg-white/5 dark:text-gray-200">{{ $file }}</div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Keine tracked Änderungen gefunden.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Geänderte Release-Dateien</h3>
                            <span class="text-xs text-gray-400">{{ count($changedFiles) }}</span>
                        </div>

                        <div class="mt-4 space-y-2">
                            @forelse ($changedFiles as $file)
                                <div class="rounded-xl bg-primary-50 px-3 py-2 font-mono text-xs text-primary-700 dark:bg-primary-500/10 dark:text-primary-200">{{ $file }}</div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Keine Release-Dateien geändert.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Blockierte Dateien</h3>
                            <span class="text-xs text-gray-400">{{ count($blockedFiles) }}</span>
                        </div>

                        <div class="mt-4 space-y-2">
                            @forelse ($blockedFiles as $file)
                                <div class="rounded-xl bg-danger-50 px-3 py-2 font-mono text-xs text-danger-700 dark:bg-danger-500/10 dark:text-danger-200">{{ $file }}</div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Keine blockierten Dateien erkannt.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50/80 p-4 dark:border-white/10 dark:bg-white/5">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Freigegebene Update-Pfade</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($updatePaths as $path)
                            <span class="inline-flex rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                                {{ $path }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Letzte Update-Läufe" description="Jeder Lauf wird mit Status, Zielversion und vollständigem Log protokolliert.">
                <div class="space-y-3">
                    @forelse ($runs as $run)
                        <button
                            type="button"
                            wire:click="selectRun({{ $run['id'] }})"
                            @class([
                                'w-full rounded-2xl border px-4 py-3 text-left transition',
                                'border-primary-300 bg-primary-50 dark:border-primary-500/40 dark:bg-primary-500/10' => $selectedRun && $selectedRun['id'] === $run['id'],
                                'border-gray-200 bg-white hover:border-gray-300 dark:border-white/10 dark:bg-white/5 dark:hover:border-white/20' => ! ($selectedRun && $selectedRun['id'] === $run['id']),
                            ])
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                            'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' => $this->badgeColor($run['status']) === 'success',
                                            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300' => $this->badgeColor($run['status']) === 'warning',
                                            'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' => $this->badgeColor($run['status']) === 'danger',
                                            'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300' => in_array($this->badgeColor($run['status']), ['gray', 'primary'], true),
                                        ])>
                                            {{ $this->badgeLabel($run['status']) }}
                                        </span>

                                        <span class="text-xs uppercase tracking-[0.2em] text-gray-400">{{ $run['mode'] === 'automatic' ? 'Auto' : 'Manuell' }}</span>
                                    </div>

                                    <p class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $run['summary'] ?: 'Ohne Zusammenfassung' }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $run['local_version'] ?: '—' }} → {{ $run['target_version'] ?: '—' }}
                                        · {{ $run['started_at']?->translatedFormat('d. M Y · H:i') ?? 'unbekannt' }}
                                    </p>
                                </div>

                                <div class="text-right text-xs text-gray-400">
                                    {{ $run['triggered_by'] ?: 'System' }}
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                            Bisher wurde noch kein Update-Lauf protokolliert.
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section heading="Log-Ausgabe" description="Ausgewählter Lauf mit vollständiger technischer Ausgabe.">
            @if ($selectedRun)
                <div class="grid gap-4 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="space-y-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-400">Zusammenfassung</p>
                            <p class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">{{ $selectedRun['summary'] ?: 'Ohne Zusammenfassung' }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Status</p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $this->badgeLabel($selectedRun['status']) }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Modus</p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $selectedRun['mode'] === 'automatic' ? 'Automatisch' : 'Manuell' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Lokal</p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $selectedRun['local_version'] ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Ziel</p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $selectedRun['target_version'] ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Gestartet</p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $selectedRun['started_at']?->translatedFormat('d. M Y · H:i:s') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Beendet</p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $selectedRun['ended_at']?->translatedFormat('d. M Y · H:i:s') ?? '—' }}</p>
                            </div>
                        </div>

                        @if (($selectedRun['changed_files'] ?? []) !== [])
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-gray-400">Geänderte Dateien</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($selectedRun['changed_files'] as $file)
                                        <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                                            {{ $file }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-950 dark:border-white/10">
                        <pre class="max-h-[34rem] overflow-auto px-4 py-4 text-xs leading-6 whitespace-pre-wrap text-gray-100">{{ $selectedRun['log_output'] ?: 'Für diesen Lauf liegt noch keine Log-Ausgabe vor.' }}</pre>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                    Noch kein Lauf ausgewählt.
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
