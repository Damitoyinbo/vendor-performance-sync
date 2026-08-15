<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ShopifyAuthController extends Controller
{
    /**
     * Step 1: redirect the merchant to Shopify's OAuth consent screen.
     */
    public function install(Request $request): RedirectResponse
    {
        $shop = $this->validateShopDomain($request->query('shop'));

        $state = bin2hex(random_bytes(16));
        session(['shopify_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('shopify.api_key'),
            'scope' => config('shopify.scopes'),
            'redirect_uri' => rtrim(config('shopify.app_url'), '/') . '/shopify/callback',
            'state' => $state,
        ]);

        return redirect("https://{$shop}/admin/oauth/authorize?{$query}");
    }

    /**
     * Step 2: verify the callback (HMAC + state), then exchange the
     * temporary authorization code for a permanent access token.
     */
    public function callback(Request $request): RedirectResponse
    {
        $shop = $this->validateShopDomain($request->query('shop'));

        abort_unless(
            $request->query('state') === session('shopify_oauth_state'),
            403,
            'Invalid OAuth state.'
        );

        abort_unless($this->verifyHmac($request->query()), 403, 'Invalid HMAC signature.');

        $client = new Client();
        $response = $client->post("https://{$shop}/admin/oauth/access_token", [
            'json' => [
                'client_id' => config('shopify.api_key'),
                'client_secret' => config('shopify.api_secret'),
                'code' => $request->query('code'),
            ],
        ]);

        $payload = json_decode((string) $response->getBody(), true);

        Shop::updateOrCreate(
            ['domain' => $shop],
            ['access_token' => $payload['access_token'], 'scope' => $payload['scope'] ?? null]
        );

        return redirect('/dashboard?shop=' . $shop);
    }

    private function validateShopDomain(?string $shop): string
    {
        abort_unless(
            $shop && preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*\.myshopify\.com$/', $shop),
            400,
            'Invalid shop domain.'
        );

        return $shop;
    }

    /**
     * Verify the request came from Shopify by recomputing the HMAC over the
     * query string (minus `hmac` itself) with the app's client secret and
     * comparing it in constant time.
     */
    private function verifyHmac(array $query): bool
    {
        $hmac = $query['hmac'] ?? '';
        unset($query['hmac'], $query['signature']);

        ksort($query);
        $message = http_build_query($query);

        $computed = hash_hmac('sha256', $message, (string) config('shopify.api_secret'));

        return hash_equals($computed, (string) $hmac);
    }
}
