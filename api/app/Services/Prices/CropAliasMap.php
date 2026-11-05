<?php

declare(strict_types=1);

namespace App\Services\Prices;

/**
 * Maps free-text crop names (AMIS/KAMIS commodity names, market lingo) to
 * catalogue crop slugs. Exact match first, then a prefix fallback for
 * short names. Unmatched names are reported by the importer — extend the
 * alias table from the report (the feedback loop).
 */
final class CropAliasMap
{
    /**
     * Alias (lowercased) => crop slug. Keep in one place — the importer
     * report feeds this table.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'tomato' => 'tomato', 'tomatoes' => 'tomato',
        'sukuma wiki' => 'kale', 'kale' => 'kale', 'kales' => 'kale', 'collard greens' => 'kale',
        'cabbage' => 'cabbage', 'cabbages' => 'cabbage',
        'onion' => 'bulb-onion', 'onions' => 'bulb-onion', 'dry onion' => 'bulb-onion', 'dry onions' => 'bulb-onion', 'onions (dry)' => 'bulb-onion',
        'french bean' => 'french-beans', 'french beans' => 'french-beans',
        'capsicum' => 'capsicum', 'capsicums' => 'capsicum', 'hoho' => 'capsicum', 'sweet pepper' => 'capsicum', 'green pepper' => 'capsicum',
        'chili' => 'chili', 'chillies' => 'chili', 'chilies' => 'chili', 'chilli' => 'chili',
        'eggplant' => 'eggplant', 'eggplants' => 'eggplant', 'brinjal' => 'eggplant', 'brinjals' => 'eggplant',
        'potato' => 'potato', 'potatoes' => 'potato', 'irish potato' => 'potato', 'irish potatoes' => 'potato', 'potatoes (irish)' => 'potato',
        'sweet potato' => 'sweet-potato', 'sweet potatoes' => 'sweet-potato',
        'watermelon' => 'watermelon', 'watermelons' => 'watermelon',
        'amaranthus' => 'amaranthus', 'amaranth' => 'amaranthus', 'mchicha' => 'amaranthus',
        'black nightshade' => 'black-nightshade', 'african nightshade' => 'black-nightshade', 'managu' => 'black-nightshade',
        'cowpea leaves' => 'cowpea-leaves', 'cowpea' => 'cowpea-leaves', 'cowpeas' => 'cowpea-leaves', 'kunde' => 'cowpea-leaves',
        'spinach' => 'spinach', 'swiss chard' => 'spinach', 'chard' => 'spinach',
        'carrot' => 'carrot', 'carrots' => 'carrot',
        'butternut' => 'butternut', 'butternuts' => 'butternut', 'butternut squash' => 'butternut',
        'coriander' => 'coriander', 'dhania' => 'coriander',
        'pawpaw' => 'pawpaw', 'pawpaws' => 'pawpaw', 'papaya' => 'pawpaw',
        'snow pea' => 'snow-peas', 'snow peas' => 'snow-peas', 'sugar snap' => 'snow-peas',
        'avocado' => 'avocado', 'avocados' => 'avocado', 'hass avocado' => 'avocado',
        'banana' => 'banana', 'bananas' => 'banana', 'cooking banana' => 'banana',
        'mango' => 'mango', 'mangoes' => 'mango',
        'passion fruit' => 'passion-fruit', 'passion fruits' => 'passion-fruit', 'passion' => 'passion-fruit',
        'maize' => 'maize', 'dry maize' => 'maize', 'maize (dry)' => 'maize', 'green maize' => 'maize',
        'bean' => 'beans', 'beans' => 'beans', 'dry beans' => 'beans', 'beans (dry)' => 'beans',
    ];

    public static function toSlug(string $cropName): ?string
    {
        $key = mb_strtolower(trim($cropName));

        if (isset(self::ALIASES[$key])) {
            return self::ALIASES[$key];
        }

        // Prefix fallback for short names ("pea" -> snow peas). Only for
        // names of 4+ chars so typos don't silently map somewhere random.
        if (mb_strlen($key) >= 4) {
            foreach (self::ALIASES as $alias => $slug) {
                if (str_starts_with($alias, $key)) {
                    return $slug;
                }
            }
        }

        return null;
    }
}
