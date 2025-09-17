<?php

namespace Ziptied\Bedrock\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;
use Ziptied\Bedrock\Domain\Pipelines\HandleWebhookPipeline;

class WebhookController extends Controller
{
    public function __construct(
        private readonly HandleWebhookPipeline $pipeline
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            ($this->pipeline)($request);
        } catch (Throwable $exception) {
            $this->logger()->error('Azure Marketplace webhook error', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }

        return response()->noContent();
    }

    private function logger(): LoggerInterface
    {
        $channel = config('azure_marketplace.logging.channel');

        return $channel ? Log::channel($channel) : Log::getLogger();
    }
}
