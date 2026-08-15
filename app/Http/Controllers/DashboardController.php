<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\ShopifyGraphQLClient;
use App\Services\VendorPerformanceAggregator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(Request $request): View
    {
        $shop = Shop::where('domain', $request->query('shop'))->firstOrFail();

        $client = new ShopifyGraphQLClient($shop->domain, $shop->access_token);
        $aggregator = new VendorPerformanceAggregator(config('shopify.low_stock_threshold'));

        $vendors = $aggregator->aggregate($client->fetchAllProducts());

        return view('dashboard', ['vendors' => $vendors, 'shop' => $shop->domain]);
    }
}
