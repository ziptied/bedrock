<?php

namespace Ziptied\Bedrock\Domain\Actions;

use Illuminate\Http\Request;
use Ziptied\Bedrock\Domain\Exceptions\WebhookSignatureException;
use Ziptied\Bedrock\Domain\Support\HashedSignature;

class VerifyWebhookSignatureAction
{
    public function handle(Request $request): void
    {
        $config = config('azure_marketplace.webhook');

        $this->assertIpAllowed($request, $config['ip_allowlist'] ?? []);
        $this->assertClientCertificate($request, (bool) ($config['enforce_tls_client_cert'] ?? false));
        $this->assertSignature($request, $config['shared_secret'] ?? null, $config['signature_header'] ?? 'X-Marketplace-Signature');

        $request->attributes->set('azure_marketplace.signature_valid', true);
    }

    private function assertIpAllowed(Request $request, array $allowlist): void
    {
        if (empty($allowlist)) {
            return;
        }

        $clientIp = $request->ip();
        if (!in_array($clientIp, $allowlist, true)) {
            throw new WebhookSignatureException('Request IP not allowed.');
        }
    }

    private function assertClientCertificate(Request $request, bool $required): void
    {
        if (!$required) {
            return;
        }

        if (!$request->server('SSL_CLIENT_CERT')) {
            throw new WebhookSignatureException('Client certificate missing.');
        }
    }

    private function assertSignature(Request $request, ?string $secret, string $headerName): void
    {
        if (!$secret) {
            return;
        }

        $provided = $request->header($headerName);
        $rawBody = $request->getContent();

        if (!HashedSignature::verify($provided, $secret, $rawBody)) {
            throw new WebhookSignatureException('Invalid webhook signature.');
        }
    }
}
