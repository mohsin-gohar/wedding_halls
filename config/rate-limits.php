<?php

return [
    // Defaults remain production-safe. Browser CI may raise these limits so
    // a multi-viewport crawl does not test the rate limiter instead of the UI.
    'public_pages_per_minute' => (int) env('PUBLIC_PAGES_RATE_LIMIT', 120),
    'public_availability_per_minute' => (int) env('PUBLIC_AVAILABILITY_RATE_LIMIT', 60),
    'reload_protection_per_minute' => (int) env('RELOAD_PROTECTION_RATE_LIMIT', 5),
];
