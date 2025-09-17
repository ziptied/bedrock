<?php

namespace Ziptied\Bedrock\Domain\Enums;

enum OperationStatus: string
{
    case InProgress = 'InProgress';
    case Succeeded = 'Succeeded';
    case Failed = 'Failed';
}
