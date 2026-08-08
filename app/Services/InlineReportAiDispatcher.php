<?php

namespace App\Services;

use App\Contracts\ReportAiDispatcher;
use App\Data\AiProcessingResult;
use App\Models\ViolationReport;

class InlineReportAiDispatcher implements ReportAiDispatcher
{
    public function __construct(private readonly ProcessReportAi $processor) {}

    public function dispatch(ViolationReport $report): AiProcessingResult
    {
        return $this->processor->process($report, ProcessReportAi::TRIGGER_INITIAL);
    }
}
