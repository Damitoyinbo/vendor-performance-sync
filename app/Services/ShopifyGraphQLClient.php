<?php

namespace App\Services;

use GuzzleHttp\Client;

class ShopifyGraphQLClient
{
    private Client $http;

    public function __construct(private string $shopDomain, private string $accessToken)
    {
        $this->http = new Client([
            'base_uri' => "https://{$shopDomain}/admin/api/" . config('shopify.api_version') . '/',
            'headers' => [
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Page through the full product/variant catalog using cursor-based
     * pagination, returning a flat array of raw GraphQL product nodes.
     *
     * @return array<int, array>
     */
    public function fetchAllProducts(int $pageSize = 250): array
    {
        $products = [];
        $cursor = null;
        $hasNextPage = true;

        while ($hasNextPage) {
            $response = $this->query($this->productsQuery(), [
                'first' => $pageSize,
                'after' => $cursor,
            ]);

            $edges = $response['data']['products']['edges'] ?? [];

            foreach ($edges as $edge) {
                $products[] = $edge['node'];
            }

            $pageInfo = $response['data']['products']['pageInfo'] ?? ['hasNextPage' => false];
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $cursor = $hasNextPage ? end($edges)['cursor'] : null;
        }

        return $products;
    }

    private function query(string $query, array $variables = []): array
    {
        $response = $this->http->post('graphql.json', [
            'json' => ['query' => $query, 'variables' => $variables],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    private function productsQuery(): string
    {
        return <<<'GRAPHQL'
        query ProductsWithInventory($first: Int!, $after: String) {
          products(first: $first, after: $after) {
            pageInfo { hasNextPage }
            edges {
              cursor
              node {
                id
                title
                vendor
                variants(first: 100) {
                  edges {
                    node {
                      id
                      sku
                      price
                      inventoryQuantity
                    }
                  }
                }
              }
            }
          }
        }
        GRAPHQL;
    }
}
