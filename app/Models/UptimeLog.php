<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Database\Factories\UptimeLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_id', 'status', 'recorded_at'])]
class UptimeLog extends Model
{
    /** @use HasFactory<UptimeLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::saved(function (self $uptimeLog): void {
            $uptimeLog->service?->refreshUptimePercentage();
        });

        static::deleted(function (self $uptimeLog): void {
            Service::query()->find($uptimeLog->service_id)?->refreshUptimePercentage();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'recorded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
