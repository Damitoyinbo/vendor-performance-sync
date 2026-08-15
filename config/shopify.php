<?php

return [
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'scopes' => env('SHOPIFY_SCOPES', 'read_products'),
    'app_url' => env('SHOPIFY_APP_URL'),
    'api_version' => env('SHOPIFY_API_VERSION', '2024-10'),
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),
];
