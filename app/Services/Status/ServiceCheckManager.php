<?php

namespace App\Services\Status;

use App\Enums\ServiceCheckType;
use App\Models\Service;
use App\Services\Status\Checks\CheckResult;
use App\Services\Status\Checks\DatabaseHealthChecker;
use App\Services\Status\Checks\PingHealthChecker;
use App\Services\Status\Checks\TcpHealthChecker;
use App\Services\Status\Checks\WebsiteHealthChecker;

class ServiceCheckManager
{
    public function __construct(
        protected WebsiteHealthChecker $websiteHealthChecker,
        protected TcpHealthChecker $tcpHealthChecker,
        protected PingHealthChecker $pingHealthChecker,
        protected DatabaseHealthChecker $databaseHealthChecker,
    ) {}

    public function check(Service $service): CheckResult
    {
        return match ($service->check_type ?? ServiceCheckType::Website) {
            ServiceCheckType::Website => $this->websiteHealthChecker->check($service),
            ServiceCheckType::Tcp => $this->tcpHealthChecker->check($service),
            ServiceCheckType::Ping => $this->pingHealthChecker->check($service),
            ServiceCheckType::Database => $this->databaseHealthChecker->check($service),
        };
    }
}
