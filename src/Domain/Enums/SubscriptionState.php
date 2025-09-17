<?php

namespace Ziptied\Bedrock\Domain\Enums;

enum SubscriptionState: string
{
    case Active = 'Active';
    case Suspended = 'Suspended';
    case Unsubscribed = 'Unsubscribed';
    case PendingActivation = 'PendingActivation';
    case PendingFulfillmentStart = 'PendingFulfillmentStart';
    case NotStarted = 'NotStarted';
    case Deleted = 'Deleted';
}
