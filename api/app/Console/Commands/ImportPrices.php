<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Prices\PriceCsvImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * panda:import-prices — imports the normalized price CSV into market_prices.
 *
 * Sources: --file=... for manual/admin drops (always available), or the
 * configured feed URL when prices.feed_enabled is true (flag-gated so prod
 * never makes surprise external calls). The daily schedule in
 * bootstrap/app.php is inert until the flag flips.
 */
class ImportPrices extends Command
{
    protected $signature = 'panda:import-prices
                            {--file= : local CSV file to import (always available)}';

    protected $description = 'Import market prices from the normalized CSV contract (AMIS/KAMIS).';

    public function handle(PriceCsvImporter $importer): int
    {
        $csv = $this->source();

        if ($csv === null) {
            $this->info('Price feed not configured (PRICE_FEED_ENABLED / PRICE_FEED_URL). Nothing to do.');

            return self::SUCCESS;
        }

        if ($csv === false) {
            $this->error('Could not read the CSV source.');

            return self::FAILURE;
        }

        $report = $importer->import($csv, (string) config('prices.source'));

        $this->info(sprintf(
            'Prices: %d imported, %d skipped (already present), %d invalid, %d unmatched crops.',
            $report['imported'],
            $report['skipped'],
            $report['invalid'],
            $report['unmatched'],
        ));

        if ($report['unmatched_sample'] !== []) {
            $this->warn('Unmatched crop names (extend CropAliasMap): '.implode(', ', $report['unmatched_sample']));
        }

        return self::SUCCESS;
    }

    /**
     * @return string|false|null csv body, false on read failure, null when no source is configured
     */
    private function source(): string|false|null
    {
        $file = $this->option('file');

        if (is_string($file) && $file !== '') {
            $content = @file_get_contents($file);

            return $content === false ? false : $content;
        }

        if (! (bool) config('prices.feed_enabled') || (string) config('prices.feed_url') === '') {
            return null;
        }

        $response = Http::timeout(60)->get((string) config('prices.feed_url'));

        return $response->successful() ? $response->body() : false;
    }
}
