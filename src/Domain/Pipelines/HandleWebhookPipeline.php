<?php

namespace Ziptied\Bedrock\Domain\Pipelines;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use Ziptied\Bedrock\Domain\Actions\EnsureIdempotencyAction;
use Ziptied\Bedrock\Domain\Actions\VerifyWebhookSignatureAction;
use Ziptied\Bedrock\Domain\Enums\ActionType;
use Ziptied\Bedrock\Jobs\ProcessWebhookJob;

class HandleWebhookPipeline
{
    public function __construct(
        private readonly VerifyWebhookSignatureAction $verify,
        private readonly EnsureIdempotencyAction $idempotency
    ) {
    }

    public function __invoke(Request $request): void
    {
        $this->verify->handle($request);

        $payload = $this->decodePayload($request);
        $actionValue = $payload['action'] ?? ActionType::Pending->value;
        $action = ActionType::tryFrom($actionValue) ?? ActionType::Pending;
        $operationId = $payload['id'] ?? throw new InvalidArgumentException('Webhook payload missing operation id.');

        $operation = $this->idempotency->handle($operationId, $action, $payload);

        Bus::dispatch(new ProcessWebhookJob($operation->id));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(Request $request): array
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            $decoded = json_decode($request->getContent(), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (!is_array($payload) || empty($payload)) {
            throw new InvalidArgumentException('Invalid webhook payload.');
        }

        return $payload;
    }
}
