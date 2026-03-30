<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AppSettingsService;
use App\Services\ApplicationUpdateService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApplicationUpdateController extends Controller
{
    public function __construct(
        protected ApplicationUpdateService $applicationUpdateService,
        protected AppSettingsService $appSettingsService,
        protected AuditLogService $auditLogService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $this->ensureAuthorized($request);

        return response()->json($this->applicationUpdateService->status());
    }

    public function run(Request $request): JsonResponse
    {
        $this->ensureAuthorized($request);

        $result = $this->applicationUpdateService->run(
            actorUserId: $request->user()?->id,
            automatic: false,
        );

        $this->auditLogService->record(
            'application_update_run_requested',
            'system_update',
            null,
            [
                'status' => $result['status'] ?? null,
                'message' => $result['message'] ?? null,
                'run_id' => data_get($result, 'run.id'),
            ],
            $request->user()?->id,
            $request,
        );

        return response()->json($result);
    }

    public function preferences(Request $request): JsonResponse
    {
        $this->ensureAuthorized($request);

        $validated = $request->validate([
            'auto_update_enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['auto_update_enabled'];

        $this->appSettingsService->update([
            'auto_update_enabled' => $enabled ? '1' : '0',
        ]);

        $this->auditLogService->record(
            'application_update_preferences_updated',
            'system_update',
            null,
            ['auto_update_enabled' => $enabled],
            $request->user()?->id,
            $request,
        );

        return response()->json([
            'auto_update_enabled' => $enabled,
        ]);
    }

    public function runShow(Request $request, int $runId): JsonResponse
    {
        $this->ensureAuthorized($request);

        $run = $this->applicationUpdateService->runDetail($runId);

        abort_if($run === null, 404);

        return response()->json($run);
    }

    protected function ensureAuthorized(Request $request): void
    {
        if (! $request->user()) {
            throw new HttpResponseException(
                response()->json(['message' => 'Nicht angemeldet.'], 401),
            );
        }

        if (! filled($request->user()?->email_verified_at)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Die E-Mail-Adresse ist noch nicht bestätigt.'], 403),
            );
        }
    }
}
