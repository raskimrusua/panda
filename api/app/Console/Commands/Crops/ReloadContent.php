<?php

namespace App\Console\Commands\Crops;

use App\Services\Content\ContentLoader;
use Illuminate\Console\Command;
use RuntimeException;

class ReloadContent extends Command
{
    protected $signature = 'crops:content:reload {--slug= : Reload only the specified crop slug}';

    protected $description = 'Cache-bust + reload crop JSON content from resources/content/crops/*.json into Redis.';

    public function handle(ContentLoader $loader): int
    {
        if ($slug = $this->option('slug')) {
            $this->info("Reloading single crop: {$slug}");
            try {
                $content = $loader->loadCropFile($slug);
                $this->line("  ✓ {$slug} ({$content['name_en']} / {$content['name_sw']}) — {$content['category']}/{$content['harvest_type']}");
            } catch (RuntimeException $e) {
                $this->error('  ✗ '.$e->getMessage());

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        $this->info('Flushing existing content cache...');
        $loader->flush();

        $this->info('Loading all crop content files...');
        $loaded = $loader->loadAllCrops();

        $this->newLine();
        $this->info(sprintf('Loaded %d crop content file(s):', count($loaded)));
        foreach ($loaded as $slug => $content) {
            $this->line("  ✓ {$slug} ({$content['name_en']})");
        }

        return self::SUCCESS;
    }
}
