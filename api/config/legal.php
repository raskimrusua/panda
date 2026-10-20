<?php

/*
| Legal & Compliance constants — Kenya jurisdiction (Data Protection Act 2019).
|
| Bump the version strings whenever the corresponding policy text on the
| marketing site changes. ConsentGate middleware compares user.terms_version
| against legal.terms_version on every authenticated request and emits a 409
| TERMS_VERSION_OUTDATED if they diverge.
|
| Disease + market-price disclaimers ride in the API response payload (not
| just the UI banner) so offline-cached + programmatic API consumers cannot
| receive advisory data without the disclaimer.
*/

return [

    /*
    | Terms of Service version. Format: v<major>_YYYY-MM-DD. Stamped on
    | UserConsent rows + on User.terms_version. Drives the consent-gate
    | check + the /policies/active payload.
    */
    'terms_version' => env('TERMS_VERSION', 'v1_2026-05-15'),

    /*
    | Privacy Policy version. Bumped independently of terms — the user
    | must accept both on a re-consent.
    */
    'privacy_version' => env('PRIVACY_VERSION', 'v1_2026-05-15'),

    /*
    | Marketing site origin — used in the /policies/active response to
    | build absolute URLs (e.g. https://panda.shira.farm/terms) that the
    | PWA can link to in checkboxes + the /accept-terms page.
    */
    'marketing_url' => env('MARKETING_URL', 'https://panda.shira.farm'),

    /*
    | Disease detection disclaimer — appears in every DiseaseDetection API
    | response. Names KEPHIS + PCPB so farmers know who actually regulates
    | the products that may be recommended. Two sentences, ~50 words.
    */
    'disease_disclaimer' => env(
        'DISEASE_DISCLAIMER',
        'Photo-based diagnoses are estimates, not a substitute for inspection by a qualified agronomist. '
        .'Treatment recommendations follow KEPHIS and PCPB-registered product guidance — always verify '
        .'pesticide registration, withholding periods, and dosage with a licensed dealer before applying.'
    ),

    /*
    | Market price advisory disclaimer — appears in every MarketPrice API
    | response. Prices come from public aggregators (NAFIS, county boards)
    | and lag the actual market by 1–3 days.
    */
    'price_disclaimer' => env(
        'PRICE_DISCLAIMER',
        'Market prices are aggregated from public sources (NAFIS, county boards) and may be 1–3 days delayed. '
        .'Verify the price directly with the buyer before transacting.'
    ),

];
