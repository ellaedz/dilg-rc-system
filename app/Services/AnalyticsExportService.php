<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportService
{
    public function downloadCsv(Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new \RuntimeException('Unable to create the CSV export stream.');
            }

            // UTF-8 BOM keeps Filipino text readable when opened in Microsoft Excel.
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Report Number',
                'Submitted At',
                'Barangay',
                'Description',
                'Citizen Classification',
                'AI Suggestion',
                'AI Confidence (%)',
                'Photo Match (%)',
                'Text Confidence (%)',
                'Official Classification',
                'Status',
                'Verification Status',
                'Response Time (Hours)',
            ]);

            $query->orderBy('id')->chunkById(250, function ($reports) use ($stream): void {
                foreach ($reports as $report) {
                    fputcsv($stream, array_map($this->sanitizeCsvCell(...), [
                        $report->report_number ?: $report->report_id,
                        optional($report->created_at)->format('Y-m-d H:i:s'),
                        $report->effective_barangay ?: 'Unassigned',
                        $report->description,
                        $report->citizen_violation_type_label,
                        $report->final_ai_prediction ?: $report->predicted_violation_category,
                        $this->confidencePercent($report->final_ai_confidence ?: $report->confidence_score),
                        $this->confidencePercent($report->ai_image_confidence),
                        $this->confidencePercent($report->text_confidence),
                        $report->official_violation_type,
                        $report->status,
                        $report->verification_status,
                        $report->response_time_hours,
                    ]));
                }
            });

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function downloadPdf(View $view, string $filename): Response
    {
        return Pdf::loadView($view->name(), array_merge($view->getData(), ['pdfExport' => true]))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function confidencePercent(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $confidence = (float) $value;

        if ($confidence >= 0 && $confidence <= 1) {
            $confidence *= 100;
        }

        return number_format($confidence, 1, '.', '');
    }

    private function sanitizeCsvCell(mixed $value): string
    {
        $cell = trim((string) ($value ?? ''));

        // Prevent spreadsheet programs from evaluating user-provided text as formulas.
        if (preg_match('/^[=+\-@]/', $cell) === 1) {
            return "'".$cell;
        }

        return $cell;
    }
}
