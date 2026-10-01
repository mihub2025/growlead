<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MetaOAuthService
{
    public function configured(): bool
    {
        return filled(config('services.meta.client_id')) && filled(config('services.meta.client_secret'));
    }

    public function redirectUri(): string
    {
        return route('crm.integrations.meta.callback');
    }

    public function makeState(): string
    {
        $state = Str::random(40);
        session(['meta_oauth_state' => $state]);

        return $state;
    }

    public function authorizationUrl(string $state): string
    {
        $query = [
            'client_id' => config('services.meta.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'response_type' => 'code',
        ];

        $query['scope'] = config('services.meta.scopes');
        $query['auth_type'] = 'rerequest';

        $configId = config('services.meta.login_config_id');
        if (filled($configId)) {
            $query['config_id'] = $configId;
        }

        return 'https://www.facebook.com/'.$this->version().'/dialog/oauth?'.http_build_query($query);
    }

    public function complete(User $user, string $code): Integration
    {
        $token = $this->requestToken([
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
        ]);
        $access = $token['access_token'] ?? null;
        if (! $access) {
            throw new RuntimeException('Meta did not return an access token.');
        }

        try {
            $longLived = $this->requestToken([
                'grant_type' => 'fb_exchange_token',
                'fb_exchange_token' => $access,
            ]);
            if (! empty($longLived['access_token'])) {
                $token = array_merge($token, $longLived);
                $access = $longLived['access_token'];
            }
        } catch (\Throwable) {
            // Keep the short-lived token if the exchange is unavailable.
        }

        $profile = $this->graphGet('/me', $access, ['fields' => 'id,name']);
        $expiresIn = (int) ($token['expires_in'] ?? 0);
        $existing = Integration::where('organization_id', $user->organization_id)->where('provider', 'meta')->first();
        $sameUser = (string) data_get($existing?->credentials, 'user_id', '') === (string) ($profile['id'] ?? '');
        $settings = array_merge($existing?->settings ?? [], [
            'connected_via' => 'oauth',
            'account_name' => $profile['name'] ?? 'Meta account',
            'account_id' => $profile['id'] ?? null,
            'business_catalog' => [],
            'available_businesses' => [],
        ]);
        if (! $sameUser) {
            $settings['selected_businesses'] = [];
        }

        $credentials = [
            'token' => $access,
            'token_type' => $token['token_type'] ?? 'bearer',
            'expires_at' => $expiresIn ? now()->addSeconds($expiresIn)->toIso8601String() : null,
            'user_id' => $profile['id'] ?? null,
        ];
        $existingCapiToken = data_get($existing?->credentials, 'capi_access_token');
        if (filled($existingCapiToken)) {
            $credentials['capi_access_token'] = $existingCapiToken;
        }

        return Integration::updateOrCreate(
            ['organization_id' => $user->organization_id, 'provider' => 'meta'],
            [
                'name' => 'Meta Ads',
                'status' => 'connected',
                'last_error' => null,
                'credentials' => $credentials,
                'settings' => $settings,
            ]
        );
    }

    public function disconnect(User $user): void
    {
        $item = Integration::forOrganization($user->organization_id)->where('provider', 'meta')->first();
        if (! $item) {
            return;
        }

        // Preserve CAPI access token / settings so outbound CRM feedback survives OAuth reconnect.
        $capiToken = data_get($item->credentials, 'capi_access_token');
        $credentials = $capiToken ? ['capi_access_token' => $capiToken] : null;

        $item->update([
            'status' => 'disconnected',
            'credentials' => $credentials,
            'last_error' => null,
            'settings' => array_merge($item->settings ?? [], [
                'connected_via' => null,
                'account_name' => null,
                'account_id' => null,
                'selected_businesses' => [],
                'available_businesses' => [],
                'business_catalog' => [],
            ]),
        ]);
    }

    protected function requestToken(array $params): array
    {
        $response = Http::timeout(20)->get('https://graph.facebook.com/'.$this->version().'/oauth/access_token', array_merge([
            'client_id' => config('services.meta.client_id'),
            'client_secret' => config('services.meta.client_secret'),
        ], $params));

        $data = $response->json() ?? [];
        if ($response->failed() || isset($data['error'])) {
            throw new RuntimeException($data['error']['message'] ?? 'Meta token request failed.');
        }

        return $data;
    }

    public function graphGet(string $path, string $token, array $query = []): array
    {
        $response = Http::timeout(45)->get('https://graph.facebook.com/'.$this->version().$path, array_merge($query, [
            'access_token' => $token,
        ]));

        $data = $response->json() ?? [];
        if ($response->failed() || isset($data['error'])) {
            throw new RuntimeException($data['error']['message'] ?? 'Meta Graph request failed.');
        }

        return $data;
    }

    public function graphPaginate(string $path, string $token, array $query = [], int $maxPages = 10): array
    {
        $items = [];
        $query['limit'] = $query['limit'] ?? 50;

        for ($page = 0; $page < $maxPages; $page++) {
            $data = $this->graphGet($path, $token, $query);
            $items = array_merge($items, $data['data'] ?? []);
            $after = $data['paging']['cursors']['after'] ?? null;
            if (! $after) {
                break;
            }
            $query['after'] = $after;
        }

        return $items;
    }

    public function grantedScopes(string $userToken): array
    {
        $appToken = config('services.meta.client_id').'|'.config('services.meta.client_secret');

        try {
            $debug = $this->graphGet('/debug_token', $appToken, ['input_token' => $userToken]);
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter(array_map('strval', data_get($debug, 'data.scopes', []))));
    }

    protected function version(): string
    {
        return trim((string) config('services.meta.graph_version', 'v21.0'), '/');
    }
}
