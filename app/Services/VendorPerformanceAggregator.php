<?php

namespace App\Services;

class VendorPerformanceAggregator
{
    public function __construct(private int $lowStockThreshold = 5)
    {
    }

    /**
     * Aggregate raw GraphQL product nodes (as returned by
     * ShopifyGraphQLClient::fetchAllProducts) into a per-vendor scorecard.
     *
     * @param array<int, array> $products
     * @return array<string, array{
     *   vendor: string,
     *   sku_count: int,
     *   low_stock_count: int,
     *   out_of_stock_count: int,
     *   inventory_value: float
     * }>
     */
    public function aggregate(array $products): array
    {
        $vendors = [];

        foreach ($products as $product) {
            $vendor = $product['vendor'] ?: 'Unknown vendor';
            $vendors[$vendor] ??= [
                'vendor' => $vendor,
                'sku_count' => 0,
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
                'inventory_value' => 0.0,
            ];

            foreach ($product['variants']['edges'] ?? [] as $edge) {
                $variant = $edge['node'];
                $quantity = (int) ($variant['inventoryQuantity'] ?? 0);
                $price = (float) ($variant['price'] ?? 0);

                $vendors[$vendor]['sku_count']++;
                $vendors[$vendor]['inventory_value'] += $price * max($quantity, 0);

                if ($quantity <= 0) {
                    $vendors[$vendor]['out_of_stock_count']++;
                } elseif ($quantity <= $this->lowStockThreshold) {
                    $vendors[$vendor]['low_stock_count']++;
                }
            }
        }

        uasort($vendors, fn ($a, $b) => $b['inventory_value'] <=> $a['inventory_value']);

        return $vendors;
    }
}
