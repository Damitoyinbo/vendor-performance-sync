# vendor-performance-sync

A Laravel app that installs into a Shopify store, pulls the full product/variant catalog via the Admin GraphQL API, and aggregates it into a per-vendor performance dashboard — SKU count, low/out-of-stock counts, and inventory value per vendor. Built as a smaller, standalone companion to the procurement/vendor-scoring work in my other repos, applied to a live Shopify store's inventory instead of a procurement database.

## What's in here

- **`app/Http/Controllers/ShopifyAuthController.php`** — Shopify's OAuth install flow: builds the authorize redirect, verifies the callback's HMAC signature against `SHOPIFY_API_SECRET`, and exchanges the temporary code for a permanent access token.
- **`app/Services/ShopifyGraphQLClient.php`** — a thin client over the Admin GraphQL API that pages through `products(first: 250, after: $cursor)` using cursor-based pagination until `pageInfo.hasNextPage` is false, so it works on catalogs larger than a single page.
- **`app/Services/VendorPerformanceAggregator.php`** — pure aggregation logic: groups variants by `product.vendor`, and for each vendor computes SKU count, low-stock count (`0 < quantity <= threshold`), out-of-stock count (`quantity <= 0`), and total inventory value (`price × quantity`, summed). This is the class the unit tests exercise directly, decoupled from any HTTP/GraphQL concerns.
- **`app/Models/Shop.php`** + migration — stores the installed shop's domain and access token after OAuth completes.
- **`resources/views/dashboard.blade.php`** — renders the aggregator's output as a sortable vendor scorecard table.
- **`tests/Unit/VendorPerformanceAggregatorTest.php`** — unit tests against `tests/Fixtures/sample_products.json`, a synthetic (fully fabricated) GraphQL response fixture modeled on a small beauty/personal-care catalog, so the aggregation logic is tested without needing a live store.

## Scope note

This repo contains the app's custom logic (controllers, services, models, one migration, views, tests) rather than a full committed Laravel skeleton (`vendor/`, generated config, etc. are intentionally omitted, as is normal practice) or a full production auth/session/queue setup. Dropped into `laravel new` with `composer require` for the listed dependencies, it runs end-to-end against a real Shopify dev store.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Set `SHOPIFY_API_KEY`, `SHOPIFY_API_SECRET`, and `SHOPIFY_SCOPES=read_products` in `.env`, then visit `/install?shop=your-dev-store.myshopify.com` to start the OAuth flow.

## Tests

```bash
php artisan test --filter=VendorPerformanceAggregatorTest
```

## Stack

Laravel, PHP, Shopify Admin GraphQL API, PHPUnit.
