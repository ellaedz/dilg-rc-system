<?php

namespace App\Contracts;

use App\Data\AiProcessingResult;
use App\Models\ViolationReport;

interface ReportAiDispatcher
{
    public function dispatch(ViolationReport $report): AiProcessingResult;
}
