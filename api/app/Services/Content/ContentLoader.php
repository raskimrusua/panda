<?php

namespace App\Services\Content;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use RuntimeException;

/**
 * ContentLoader — loads + validates crop/disease JSON content into Redis cache.
 *
 * Source of truth: resources/content/{crops,diseases}/*.json
 * Validated against: resources/content/schema/{crop,disease}.schema.json
 * Cached in: Redis (or array driver in tests) — 24-hour TTL, busted via `crops:content:reload`.
 *
 * Per CLAUDE.md L2: agronomist authors via Filament editor (P1+); export command
 * regenerates JSON files from DB; this loader reads JSON into Redis at startup.
 *
 * Performance: a single load() call validates + caches all crop files. Subsequent
 * getCrop($slug) calls are Redis lookups (~1ms p99). Skip rebuild if cache warm.
 */
class ContentLoader
{
    private const CACHE_PREFIX = 'panda:content:';

    private const CACHE_TTL = 86400; // 24 hours

    private const ALL_CROPS_KEY = self::CACHE_PREFIX.'crops:all';

    private Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator;
        $this->validator->setMaxErrors(20);
    }

    /**
     * Load all crop JSON files into cache. Idempotent + safe to re-run.
     *
     * @return array<string, array<string, mixed>> Map of slug => crop content
     */
    public function loadAllCrops(): array
    {
        $cropsDir = $this->cropsDir();
        $loaded = [];

        foreach (File::glob($cropsDir.'/*.json') as $path) {
            $slug = pathinfo($path, PATHINFO_FILENAME);
            $content = $this->loadCropFile($slug);
            $loaded[$slug] = $content;
        }

        Cache::put(self::ALL_CROPS_KEY, array_keys($loaded), self::CACHE_TTL);

        return $loaded;
    }

    /**
     * Load + validate a single crop file. Caches in Redis on success.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException if file missing or schema invalid.
     */
    public function loadCropFile(string $slug): array
    {
        $path = $this->cropsDir().'/'.$slug.'.json';

        if (! File::exists($path)) {
            throw new RuntimeException("Crop content file not found: {$slug}.json");
        }

        $raw = File::get($path);
        $data = json_decode($raw, false, 512, JSON_THROW_ON_ERROR);

        $schema = json_decode(File::get($this->schemaPath('crop')), false, 512, JSON_THROW_ON_ERROR);
        $result = $this->validator->validate($data, $schema);

        if (! $result->isValid()) {
            $errors = (new ErrorFormatter)->format($result->error());
            throw new RuntimeException("Schema validation failed for {$slug}.json: ".json_encode($errors));
        }

        // Re-decode as associative array for caching/consumption.
        $assoc = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        Cache::put(self::CACHE_PREFIX."crop:{$slug}", $assoc, self::CACHE_TTL);

        return $assoc;
    }

    /**
     * Get cached crop content. Loads + caches on miss.
     *
     * @return array<string, mixed>|null
     */
    public function getCrop(string $slug): ?array
    {
        return Cache::remember(
            self::CACHE_PREFIX."crop:{$slug}",
            self::CACHE_TTL,
            function () use ($slug): ?array {
                try {
                    return $this->loadCropFile($slug);
                } catch (RuntimeException) {
                    return null;
                }
            }
        );
    }

    /**
     * List slugs of all available crop content files.
     *
     * @return list<string>
     */
    public function availableCropSlugs(): array
    {
        return Cache::remember(
            self::ALL_CROPS_KEY,
            self::CACHE_TTL,
            function (): array {
                return collect(File::glob($this->cropsDir().'/*.json'))
                    ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
                    ->sort()
                    ->values()
                    ->all();
            }
        );
    }

    /** Cache-bust all content. Triggered by `crops:content:reload` command. */
    public function flush(): void
    {
        $slugs = $this->availableCropSlugs();
        Cache::forget(self::ALL_CROPS_KEY);
        foreach ($slugs as $slug) {
            Cache::forget(self::CACHE_PREFIX."crop:{$slug}");
        }
    }

    private function cropsDir(): string
    {
        return resource_path('content/crops');
    }

    private function schemaPath(string $kind): string
    {
        return resource_path("content/schema/{$kind}.schema.json");
    }
}
