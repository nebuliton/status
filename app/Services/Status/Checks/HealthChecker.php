<?php

namespace App\Services\Status\Checks;

use App\Models\Service;

interface HealthChecker
{
    public function check(Service $service): CheckResult;
}
