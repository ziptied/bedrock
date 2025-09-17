<?php

namespace Ziptied\Bedrock\Domain\Enums;

enum ActionType: string
{
    case ChangePlan = 'ChangePlan';
    case ChangeQuantity = 'ChangeQuantity';
    case Reinstate = 'Reinstate';
    case Suspend = 'Suspend';
    case Unsubscribe = 'Unsubscribe';
    case Renew = 'Renew';
    case Pending = 'Pending';
}
