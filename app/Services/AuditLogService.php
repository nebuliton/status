<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        string $event,
        string $resourceType,
        int|string|null $resourceId = null,
        array $payload = [],
        ?int $actorUserId = null,
        ?Request $request = null,
    ): void {
        Log::info('audit_log', [
            'event' => $event,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'actor_user_id' => $actorUserId,
            'payload' => $payload,
            'ip' => $request?->ip(),
            'method' => $request?->method(),
            'path' => $request?->path(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
