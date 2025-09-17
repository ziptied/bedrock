<?php

namespace Ziptied\Bedrock\Domain\Enums;

enum AckStatus: string
{
    case Pending = 'Pending';
    case Success = 'Success';
    case Failure = 'Failure';
}
