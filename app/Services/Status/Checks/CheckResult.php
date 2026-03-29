<?php

namespace App\Services\Status\Checks;

use App\Enums\ServiceStatus;
use Carbon\CarbonImmutable;

class CheckResult
{
    public readonly CarbonImmutable $checkedAt;

    public function __construct(
        public readonly ServiceStatus $status,
        public readonly string $message,
        public readonly ?int $responseTimeMs = null,
        ?CarbonImmutable $checkedAt = null,
    ) {
        $this->checkedAt = $checkedAt ?? CarbonImmutable::now();
    }
}
