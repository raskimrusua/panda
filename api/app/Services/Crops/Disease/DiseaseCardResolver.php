<?php

namespace App\Services\Crops\Disease;

use App\Services\Content\ContentLoader;

/**
 * Bridges a provider diagnosis name (Kindwise / mock catalogue) to the
 * bilingual disease card authored in resources/content/crops/<slug>.json.
 *
 * Phase A (2026-11): the scan pipeline returned only the provider's raw
 * name + confidence + PCPB treatments. The crop content layer already
 * carries severity classes, symptoms, treatment guidance and Swahili
 * translations per disease — this resolver makes that content reachable
 * from a diagnosis so the API + PWA can render it.
 *
 * Match strategy (most specific first):
 *   1. crop-scoped (when the scan carried a crop hint): exact disease
 *      `key`, then case-insensitive `name_en`, then a relaxed suffix match
 *      ("tomato early blight" → "early blight").
 *   2. global: same order across every crop file, in slug order.
 *
 * No match → null; callers fall back to raw-name rendering (unchanged).
 */
final class DiseaseCardResolver
{
    /**
     * Display-name aliases for catalogue entries whose wording differs from
     * the content keys ("Aphid Infestation" vs key "aphids"). Add here only
     * when a real provider/catalogue name is known to mismatch — every alias
     * is a documented vocabulary decision, not a guess.
     *
     * @var array<string, string>
     */
    private const NAME_ALIASES = [
        'aphid infestation' => 'aphids',
    ];

    public function __construct(private readonly ContentLoader $content) {}

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(string $diagnosis, ?string $cropSlug = null): ?array
    {
        $needle = $this->normalize($diagnosis);
        if ($needle === '') {
            return null;
        }
        $needle = self::NAME_ALIASES[$needle] ?? $needle;

        if ($cropSlug !== null) {
            $crop = $this->content->getCrop($cropSlug);
            $card = $this->matchIn($crop['diseases'] ?? [], $needle);
            if ($card !== null) {
                return $card;
            }
        }

        foreach ($this->content->availableCropSlugs() as $slug) {
            if ($slug === $cropSlug) {
                continue;
            }
            $crop = $this->content->getCrop($slug);
            $card = $this->matchIn($crop['diseases'] ?? [], $needle);
            if ($card !== null) {
                return $card;
            }
        }

        return null;
    }

    /**
     * @param  list<mixed>  $cards
     * @return array<string, mixed>|null
     */
    private function matchIn(array $cards, string $needle): ?array
    {
        foreach ($cards as $card) {
            if (! is_array($card)) {
                continue;
            }
            if ($this->normalize((string) ($card['key'] ?? '')) === $needle) {
                return $card;
            }
            if ($this->normalize((string) ($card['name_en'] ?? '')) === $needle) {
                return $card;
            }
        }

        // Relaxed pass: provider names often carry a crop qualifier that
        // content keys do not ("tomato early blight" vs "early blight").
        foreach ($cards as $card) {
            if (! is_array($card)) {
                continue;
            }
            $cardName = $this->normalize((string) ($card['name_en'] ?? ''));
            if ($cardName !== '' && str_ends_with($needle, $cardName)) {
                return $card;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        // Underscores/dashes carry key-style names ("aphid_infestation")
        // into the same token space as display names ("Aphid Infestation").
        $value = (string) preg_replace('/[_\-\\/]+/', ' ', $value);

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
