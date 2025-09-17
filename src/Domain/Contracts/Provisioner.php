<?php

namespace Ziptied\Bedrock\Domain\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Provisioner
{
    /**
     * Ensure a local billable entity exists for the subscription being resolved.
     */
    public function provision(array $resolvePayload): Model;
}
