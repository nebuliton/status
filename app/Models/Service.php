<?php

namespace App\Models;

use App\Enums\ServiceCheckType;
use App\Enums\ServiceIconSource;
use App\Enums\ServiceStatus;
use App\Services\Status\UptimeCalculator;
use Carbon\CarbonInterface;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'status',
    'uptime_percentage',
    'group_id',
    'check_type',
    'icon_source',
    'icon_name',
    'icon_path',
    'check_enabled',
    'check_interval_seconds',
    'timeout_seconds',
    'target_url',
    'target_host',
    'target_port',
    'expected_status_code',
    'verify_ssl',
    'latency_degraded_ms',
    'latency_down_ms',
    'database_driver',
    'database_host',
    'database_port',
    'database_name',
    'database_username',
    'database_password',
    'database_query',
    'last_checked_at',
    'last_response_time_ms',
    'last_check_message',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $service): void {
            if (blank($service->slug)) {
                $service->slug = static::generateUniqueSlug($service->name);
            }

            if (blank($service->check_type)) {
                $service->check_type = ServiceCheckType::Website;
            }

            if (blank($service->icon_source)) {
                $service->icon_source = ServiceIconSource::Auto;
            }
        });

        static::updating(function (self $service): void {
            if ($service->isDirty('name')) {
                $service->slug = static::generateUniqueSlug($service->name, $service->id);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'check_type' => ServiceCheckType::class,
            'icon_source' => ServiceIconSource::class,
            'check_enabled' => 'boolean',
            'check_interval_seconds' => 'integer',
            'timeout_seconds' => 'integer',
            'target_port' => 'integer',
            'expected_status_code' => 'integer',
            'verify_ssl' => 'boolean',
            'latency_degraded_ms' => 'integer',
            'latency_down_ms' => 'integer',
            'database_port' => 'integer',
            'database_password' => 'encrypted',
            'last_checked_at' => 'datetime',
            'last_response_time_ms' => 'integer',
            'uptime_percentage' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ServiceGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class, 'group_id');
    }

    /**
     * @return HasMany<UptimeLog, $this>
     */
    public function uptimeLogs(): HasMany
    {
        return $this->hasMany(UptimeLog::class)->orderBy('recorded_at');
    }

    public function refreshUptimePercentage(): void
    {
        app(UptimeCalculator::class)->recalculateForService($this);
    }

    public function isDueForCheck(?CarbonInterface $moment = null): bool
    {
        if (! $this->check_enabled) {
            return false;
        }

        $moment ??= now();

        if ($this->last_checked_at === null) {
            return true;
        }

        return $this->last_checked_at
            ->copy()
            ->addSeconds(max(30, $this->check_interval_seconds ?? 60))
            ->lte($moment);
    }

    public function monitorTarget(): string
    {
        return match ($this->check_type ?? ServiceCheckType::Website) {
            ServiceCheckType::Website => $this->target_url ?: 'Keine URL konfiguriert',
            ServiceCheckType::Tcp => filled($this->target_host) && filled($this->target_port)
                ? "{$this->target_host}:{$this->target_port}"
                : 'Host und Port fehlen',
            ServiceCheckType::Ping => $this->target_host ?: 'Kein Zielhost konfiguriert',
            ServiceCheckType::Database => collect([
                $this->database_driver ?: 'mysql',
                $this->database_host,
                $this->database_name,
            ])->filter()->whenEmpty(fn ($collection) => $collection->push('Datenbank-Konfiguration fehlt'))->join(' · '),
        };
    }

    /**
     * @return array{type: string, value: string, fallback: string}
     */
    public function resolvedIcon(): array
    {
        $fallback = $this->defaultIconName();
        $source = $this->icon_source ?? ServiceIconSource::Auto;

        if (
            ($source === ServiceIconSource::Upload)
            && filled($this->icon_path)
            && Storage::disk('public')->exists($this->icon_path)
        ) {
            return [
                'type' => 'image',
                'value' => Storage::disk('public')->url($this->icon_path),
                'fallback' => $fallback,
            ];
        }

        if (($source === ServiceIconSource::Icon) && filled($this->icon_name)) {
            return [
                'type' => 'icon',
                'value' => $this->icon_name,
                'fallback' => $fallback,
            ];
        }

        if (($this->check_type === ServiceCheckType::Website) && filled($favicon = $this->faviconUrl())) {
            return [
                'type' => 'image',
                'value' => $favicon,
                'fallback' => $fallback,
            ];
        }

        return [
            'type' => 'icon',
            'value' => $fallback,
            'fallback' => $fallback,
        ];
    }

    public function defaultIconName(): string
    {
        return match ($this->check_type ?? ServiceCheckType::Website) {
            ServiceCheckType::Website => 'browser',
            ServiceCheckType::Tcp => 'server',
            ServiceCheckType::Ping => 'signal',
            ServiceCheckType::Database => 'database',
        };
    }

    public function faviconUrl(): ?string
    {
        if (($this->check_type !== ServiceCheckType::Website) || blank($this->target_url)) {
            return null;
        }

        $parts = parse_url($this->target_url);

        if (($parts === false) || blank($parts['host'] ?? null)) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return "{$scheme}://{$host}{$port}/favicon.ico";
    }

    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
