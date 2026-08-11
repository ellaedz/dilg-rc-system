<?php

namespace App\Contracts;

use App\Data\AiProcessingResult;
use App\Models\ViolationReport;

interface ReportAiDispatcher
{
    public const TRIGGER_INITIAL = 'initial';

    public const TRIGGER_STAFF_RETRY = 'staff_retry';

    public function dispatch(
        ViolationReport $report,
        string $trigger = self::TRIGGER_INITIAL,
    ): AiProcessingResult;
}
