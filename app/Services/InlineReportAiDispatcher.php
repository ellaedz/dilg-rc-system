<?php

namespace App\Services;

use App\Contracts\ReportAiDispatcher;
use App\Data\AiProcessingResult;
use App\Models\ViolationReport;

class InlineReportAiDispatcher implements ReportAiDispatcher
{
    public function __construct(private readonly ProcessReportAi $processor) {}

    public function dispatch(
        ViolationReport $report,
        string $trigger = ReportAiDispatcher::TRIGGER_INITIAL,
    ): AiProcessingResult {
        return $this->processor->process($report, $trigger);
    }
}
