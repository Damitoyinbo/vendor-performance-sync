<?php

namespace Tests\Unit;

use App\Services\VendorPerformanceAggregator;
use PHPUnit\Framework\TestCase;

class VendorPerformanceAggregatorTest extends TestCase
{
    private function loadFixture(): array
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../Fixtures/sample_products.json'),
            true
        );
    }

    public function test_it_groups_variants_by_vendor(): void
    {
        $aggregator = new VendorPerformanceAggregator(lowStockThreshold: 5);
        $result = $aggregator->aggregate($this->loadFixture());

        $this->assertArrayHasKey('Adunni Naturals', $result);
        $this->assertArrayHasKey('Ile Botanicals', $result);
        $this->assertCount(2, $result);
    }

    public function test_it_counts_skus_per_vendor(): void
    {
        $aggregator = new VendorPerformanceAggregator(lowStockThreshold: 5);
        $result = $aggregator->aggregate($this->loadFixture());

        $this->assertSame(3, $result['Adunni Naturals']['sku_count']);
        $this->assertSame(4, $result['Ile Botanicals']['sku_count']);
    }

    public function test_it_flags_low_and_out_of_stock_using_the_threshold(): void
    {
        $aggregator = new VendorPerformanceAggregator(lowStockThreshold: 5);
        $result = $aggregator->aggregate($this->loadFixture());

        $this->assertSame(1, $result['Adunni Naturals']['low_stock_count']);
        $this->assertSame(1, $result['Adunni Naturals']['out_of_stock_count']);

        $this->assertSame(1, $result['Ile Botanicals']['low_stock_count']);
        $this->assertSame(1, $result['Ile Botanicals']['out_of_stock_count']);
    }

    public function test_it_sums_inventory_value_as_price_times_quantity(): void
    {
        $aggregator = new VendorPerformanceAggregator(lowStockThreshold: 5);
        $result = $aggregator->aggregate($this->loadFixture());

        // (18.00 * 42) + (10.00 * 3) + (14.50 * 0) = 786.00
        $this->assertEqualsWithDelta(786.00, $result['Adunni Naturals']['inventory_value'], 0.001);

        // (8.00 * 120) + (8.00 * 4) + (26.00 * 0) + (16.00 * 60) = 1952.00
        $this->assertEqualsWithDelta(1952.00, $result['Ile Botanicals']['inventory_value'], 0.001);
    }

    public function test_it_sorts_vendors_by_inventory_value_descending(): void
    {
        $aggregator = new VendorPerformanceAggregator(lowStockThreshold: 5);
        $result = $aggregator->aggregate($this->loadFixture());

        $this->assertSame(['Ile Botanicals', 'Adunni Naturals'], array_keys($result));
    }

    public function test_a_zero_or_negative_quantity_variant_never_counts_as_low_stock(): void
    {
        $aggregator = new VendorPerformanceAggregator(lowStockThreshold: 5);
        $result = $aggregator->aggregate([
            [
                'vendor' => 'Test Vendor',
                'variants' => [
                    'edges' => [
                        ['node' => ['sku' => 'A', 'price' => '10.00', 'inventoryQuantity' => 0]],
                    ],
                ],
            ],
        ]);

        $this->assertSame(0, $result['Test Vendor']['low_stock_count']);
        $this->assertSame(1, $result['Test Vendor']['out_of_stock_count']);
    }
}
