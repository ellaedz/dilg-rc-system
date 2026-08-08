<?php

namespace App\Services;

use App\Models\ViolationReport;

class AIInferenceService
{
    public function __construct(private readonly ProcessReportAi $processor) {}

    public function process(ViolationReport $report): bool
    {
        return $this->processor
            ->process($report, ProcessReportAi::TRIGGER_STAFF_RETRY)
            ->completed();
    }
}
