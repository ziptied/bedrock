<?php

namespace Ziptied\Bedrock\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ziptied\Bedrock\Domain\Actions\VerifyWebhookSignatureAction;

class VerifyMarketplaceSignature
{
    public function __construct(private readonly VerifyWebhookSignatureAction $action)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $this->action->handle($request);

        return $next($request);
    }
}
