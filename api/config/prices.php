<?php

declare(strict_types=1);

use App\Models\MarketPrice;

return [
    /*
    |--------------------------------------------------------------------------
    | Price feed
    |--------------------------------------------------------------------------
    |
    | Flag-gated daily ingestion of market prices. The scheduler in
    | bootstrap/app.php runs panda:import-prices daily; the command itself
    | exits "not configured" until feed_enabled is true and feed_url is set.
    | The normalized CSV contract is documented in docs/panda-price-feed-design.md.
    |
    */
    'feed_enabled' => env('PRICE_FEED_ENABLED', 'false') === 'true',

    'feed_url' => env('PRICE_FEED_URL'),

    'feed_time' => env('PRICE_FEED_SCHEDULE_AT', '05:00'),

    'source' => env('PRICE_FEED_SOURCE', MarketPrice::SOURCE_AMIS_KENYA),
];
