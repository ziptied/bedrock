<?php

namespace Ziptied\Bedrock\Clients;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Ziptied\Bedrock\Domain\Contracts\MarketplaceHttpClient;
use Ziptied\Bedrock\Domain\Exceptions\MarketplaceHttpException;

class DefaultMarketplaceHttpClient implements MarketplaceHttpClient
{
    public function acquireToken(): string
    {
        $cacheKey = 'azure_marketplace.access_token';

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $tenant = config('azure_marketplace.tenant_id');
            $clientId = config('azure_marketplace.client_id');
            $clientSecret = config('azure_marketplace.client_secret');
            $scope = config('azure_marketplace.token_scope');

            $response = Http::asForm()->post(
                "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token",
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => $scope,
                    'grant_type' => 'client_credentials',
                ]
            );

            if ($response->failed()) {
                $this->throwHttpException('Failed to acquire Azure Marketplace token', $response->status(), $response->json() ?? []);
            }

            $body = $response->json();

            $token = $body['access_token'] ?? null;

            if (!$token) {
                $this->throwHttpException('Azure Marketplace token response missing access_token', $response->status(), $body ?? []);
            }

            return $token;
        });
    }

    public function resolve(string $marketplaceToken, string $accessToken): array
    {
        $url = $this->fulfillmentUrl('subscriptions/resolve');

        $response = Http::withHeaders([
            'x-ms-marketplace-token' => $marketplaceToken,
            'Content-Type' => 'application/json',
        ])->withToken($accessToken)
            ->post($url);

        return $this->handleResponse($response, 'Failed to resolve subscription');
    }

    public function activate(string $subscriptionId, string $planId, int $quantity, string $accessToken): array
    {
        $url = $this->fulfillmentUrl("subscriptions/{$subscriptionId}/activate");

        $response = Http::withToken($accessToken)
            ->post($url, [
                'planId' => $planId,
                'quantity' => $quantity,
            ]);

        return $this->handleResponse($response, 'Failed to activate subscription');
    }

    public function getOperation(string $subscriptionId, string $operationId, string $accessToken): array
    {
        $url = $this->fulfillmentUrl("subscriptions/{$subscriptionId}/operations/{$operationId}");

        $response = Http::withToken($accessToken)->get($url);

        return $this->handleResponse($response, 'Failed to fetch operation');
    }

    public function patchOperation(string $subscriptionId, string $operationId, string $status, ?string $reason, string $accessToken): array
    {
        $url = $this->fulfillmentUrl("subscriptions/{$subscriptionId}/operations/{$operationId}");

        $payload = ['status' => $status];
        if ($reason) {
            $payload['reason'] = Str::limit($reason, 1000);
        }

        $response = Http::withToken($accessToken)->patch($url, $payload);

        return $this->handleResponse($response, 'Failed to patch operation');
    }

    public function emitUsageBatch(array $events, string $accessToken): array
    {
        $url = $this->meteringUrl('usageEvent');
        $payload = ['request' => $events];

        $response = Http::withToken($accessToken)->post($url, $payload);

        return $this->handleResponse($response, 'Failed to emit usage batch');
    }

    private function fulfillmentUrl(string $path): string
    {
        $base = rtrim(config('azure_marketplace.fulfillment_base'), '/');
        $apiVersion = config('azure_marketplace.fulfillment_api_version');

        return sprintf('%s/%s?api-version=%s', $base, ltrim($path, '/'), $apiVersion);
    }

    private function meteringUrl(string $path): string
    {
        $base = rtrim(config('azure_marketplace.metering_base'), '/');
        $apiVersion = config('azure_marketplace.metering_api_version');

        return sprintf('%s/%s?api-version=%s', $base, ltrim($path, '/'), $apiVersion);
    }

    private function handleResponse(Response $response, string $message): array
    {
        if ($response->failed()) {
            $this->throwHttpException($message, $response->status(), $response->json() ?? []);
        }

        $body = $response->json();

        return is_array($body) ? $body : [];
    }

    private function throwHttpException(string $message, int $status, array $payload): void
    {
        $this->logger()->error($message, [
            'status' => $status,
            'payload' => $payload,
        ]);

        throw new MarketplaceHttpException($message, $status, $payload);
    }

    private function logger(): LoggerInterface
    {
        $channel = config('azure_marketplace.logging.channel');

        return $channel ? Log::channel($channel) : Log::getLogger();
    }
}
