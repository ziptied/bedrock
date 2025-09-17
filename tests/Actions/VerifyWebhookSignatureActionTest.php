<?php

use Illuminate\Http\Request;
use Ziptied\Bedrock\Domain\Actions\VerifyWebhookSignatureAction;
use Ziptied\Bedrock\Domain\Exceptions\WebhookSignatureException;

test('verify webhook signature validates shared secret', function () {
    config()->set('azure_marketplace.webhook.shared_secret', 'secret');

    $action = new VerifyWebhookSignatureAction();
    $payload = json_encode(['hello' => 'world']);
    $signature = hash_hmac('sha256', $payload, 'secret');
    $request = Request::create('/marketplace/webhook', 'POST', [], [], [], ['REMOTE_ADDR' => '1.1.1.1'], $payload);
    $request->headers->set('X-Marketplace-Signature', $signature);

    $action->handle($request);

    expect($request->attributes->get('azure_marketplace.signature_valid'))->toBeTrue();
});

it('throws when signature invalid', function () {
    config()->set('azure_marketplace.webhook.shared_secret', 'secret');

    $action = new VerifyWebhookSignatureAction();
    $request = Request::create('/marketplace/webhook', 'POST', [], [], [], ['REMOTE_ADDR' => '1.1.1.1'], 'payload');
    $request->headers->set('X-Marketplace-Signature', 'invalid');

    $action->handle($request);
})->throws(WebhookSignatureException::class);

it('validates ip allowlist when configured', function () {
    config()->set('azure_marketplace.webhook.shared_secret', null);
    config()->set('azure_marketplace.webhook.ip_allowlist', ['203.0.113.10']);

    $action = new VerifyWebhookSignatureAction();
    $request = Request::create('/marketplace/webhook', 'POST', [], [], [], ['REMOTE_ADDR' => '203.0.113.10'], 'payload');

    $action->handle($request);

    expect($request->attributes->get('azure_marketplace.signature_valid'))->toBeTrue();
});

it('throws when ip not allowed', function () {
    config()->set('azure_marketplace.webhook.ip_allowlist', ['203.0.113.10']);

    $action = new VerifyWebhookSignatureAction();
    $request = Request::create('/marketplace/webhook', 'POST', [], [], [], ['REMOTE_ADDR' => '198.51.100.2'], 'payload');

    $action->handle($request);
})->throws(WebhookSignatureException::class);
