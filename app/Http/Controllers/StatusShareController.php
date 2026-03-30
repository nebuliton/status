<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\Status\StatusImageRenderer;
use App\Services\Status\StatusPageService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StatusShareController extends Controller
{
    public function service(Service $service, StatusPageService $statusPageService): View
    {
        $snapshot = $statusPageService->serviceSnapshot($service);

        return view('status-service', [
            'snapshot' => $snapshot,
            'service' => $snapshot['service'],
            'shareImageUrl' => route('status.service.image', [
                'service' => $service->slug,
                'v' => $snapshot['shareHash'],
            ]),
        ]);
    }

    public function serviceImage(
        Service $service,
        StatusPageService $statusPageService,
        StatusImageRenderer $statusImageRenderer,
    ): Response {
        $svg = $statusImageRenderer->renderServiceCard(
            $statusPageService->serviceSnapshot($service),
        );

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=120',
        ]);
    }

    public function overviewImage(
        StatusPageService $statusPageService,
        StatusImageRenderer $statusImageRenderer,
    ): Response {
        $svg = $statusImageRenderer->renderOverviewCard(
            $statusPageService->overviewShareSnapshot(),
        );

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=120',
        ]);
    }
}
