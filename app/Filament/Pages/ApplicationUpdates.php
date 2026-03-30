<?php

namespace App\Filament\Pages;

use App\Services\AppSettingsService;
use App\Services\ApplicationUpdateService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ApplicationUpdates extends Page
{
    protected static ?string $title = 'Anwendungs-Updates';

    protected static ?string $navigationLabel = 'Updates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected string|Width|null $maxContentWidth = 'full';

    protected ?string $subheading = 'Release-Status, sichere Update-Pfade und letzte Update-Läufe.';

    protected string $view = 'filament.pages.application-updates';

    public array $status = [];

    public array $runs = [];

    public ?int $selectedRunId = null;

    public ?array $selectedRun = null;

    public bool $autoUpdateEnabled = false;

    public function mount(ApplicationUpdateService $updateService, AppSettingsService $appSettingsService): void
    {
        $this->autoUpdateEnabled = $appSettingsService->boolean('auto_update_enabled');
        $this->refreshData($updateService, $appSettingsService);
    }

    public function refreshData(ApplicationUpdateService $updateService, AppSettingsService $appSettingsService): void
    {
        $this->status = $updateService->status();
        $this->runs = $updateService->recentRuns();
        $this->autoUpdateEnabled = $appSettingsService->boolean('auto_update_enabled');

        if ($this->selectedRunId) {
            $this->selectedRun = $updateService->runDetail($this->selectedRunId);
        } elseif ($this->runs !== []) {
            $this->selectedRunId = $this->runs[0]['id'];
            $this->selectedRun = $this->runs[0];
        } else {
            $this->selectedRun = null;
        }
    }

    public function runUpdate(ApplicationUpdateService $updateService, AppSettingsService $appSettingsService): void
    {
        $result = $updateService->run(
            actorUserId: auth()->id(),
            automatic: false,
        );

        if (data_get($result, 'run.id')) {
            $this->selectedRunId = data_get($result, 'run.id');
        }

        $this->refreshData($updateService, $appSettingsService);

        Notification::make()
            ->title(match ($result['status']) {
                'succeeded' => 'Update erfolgreich',
                'skipped' => 'Kein Update ausgeführt',
                'busy' => 'Update bereits aktiv',
                default => 'Update fehlgeschlagen',
            })
            ->body($result['message'])
            ->color(match ($result['status']) {
                'succeeded' => 'success',
                'skipped' => 'gray',
                'busy' => 'warning',
                default => 'danger',
            })
            ->send();
    }

    public function toggleAutoUpdate(AppSettingsService $appSettingsService, ApplicationUpdateService $updateService): void
    {
        $this->autoUpdateEnabled = ! $this->autoUpdateEnabled;

        $appSettingsService->update([
            'auto_update_enabled' => $this->autoUpdateEnabled ? '1' : '0',
        ]);

        $this->refreshData($updateService, $appSettingsService);

        Notification::make()
            ->title($this->autoUpdateEnabled ? 'Auto-Update aktiviert' : 'Auto-Update deaktiviert')
            ->color($this->autoUpdateEnabled ? 'success' : 'gray')
            ->send();
    }

    public function selectRun(int $runId, ApplicationUpdateService $updateService): void
    {
        $this->selectedRunId = $runId;
        $this->selectedRun = $updateService->runDetail($runId);
    }

    public function badgeColor(string $status): string
    {
        return match ($status) {
            'succeeded' => 'success',
            'running' => 'warning',
            'failed' => 'danger',
            'skipped' => 'gray',
            default => 'primary',
        };
    }

    public function badgeLabel(string $status): string
    {
        return match ($status) {
            'succeeded' => 'Erfolgreich',
            'running' => 'Läuft',
            'failed' => 'Fehlgeschlagen',
            'skipped' => 'Übersprungen',
            'busy' => 'Beschäftigt',
            default => ucfirst($status),
        };
    }
}
