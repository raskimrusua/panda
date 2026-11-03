<?php

namespace App\Console\Commands\Feedback;

use App\Models\DiseaseFeedback;
use Illuminate\Console\Command;

/**
 * Phase B retrain-queue export. Writes scan feedback to a CSV in
 * storage/app/exports so the training pipeline (or Silas) can pull
 * corrections + the original image path in batches.
 *
 * Default export = incorrect verdicts only (the retrain signal).
 * `--all` includes correct verdicts (calibration set).
 */
class ExportFeedback extends Command
{
    protected $signature = 'panda:export-feedback
        {--verdict=incorrect : correct|incorrect — rows to include}
        {--all : include every verdict, ignoring --verdict}
        {--since= : ISO datetime — only feedback created on/after}';

    protected $description = 'Export disease-scan feedback (retrain-queue input) to a CSV in storage/app/exports.';

    public function handle(): int
    {
        $query = DiseaseFeedback::query()
            ->with(['diseaseDetection.crop', 'tenant'])
            ->orderBy('created_at');

        if (! $this->option('all')) {
            $verdict = (string) $this->option('verdict');
            if (! in_array($verdict, [DiseaseFeedback::VERDICT_CORRECT, DiseaseFeedback::VERDICT_INCORRECT], true)) {
                $this->error('--verdict must be one of: correct, incorrect.');

                return self::FAILURE;
            }
            $query->where('verdict', $verdict);
        }

        $since = $this->option('since');
        if ($since !== null && $since !== '') {
            $query->where('created_at', '>=', $since);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            $this->warn('No feedback rows match the filter.');

            return self::SUCCESS;
        }

        $dir = storage_path('app/exports');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/disease-feedback-'.now()->format('Ymd-His').'.csv';
        $out = fopen($path, 'w');
        if ($out === false) {
            $this->error("Could not open {$path} for writing.");

            return self::FAILURE;
        }

        fputcsv($out, [
            'feedback_id', 'verdict', 'correction_key', 'correction_text',
            'detection_id', 'top_diagnosis', 'provider', 'confidence',
            'image_path', 'crop_slug', 'tenant_name', 'captured_at', 'created_at',
        ]);

        foreach ($rows as $feedback) {
            $detection = $feedback->diseaseDetection;
            fputcsv($out, [
                $feedback->id,
                $feedback->verdict,
                $feedback->correction_key,
                $feedback->correction_text,
                $detection?->id,
                $detection?->top_diagnosis,
                $detection?->provider,
                $detection?->confidence,
                $detection?->image_path,
                $detection?->crop?->slug,
                $feedback->tenant?->name,
                $detection?->captured_at?->toIso8601String(),
                $feedback->created_at?->toIso8601String(),
            ]);
        }
        fclose($out);

        $this->info("Exported {$rows->count()} row(s) to {$path}");

        return self::SUCCESS;
    }
}
