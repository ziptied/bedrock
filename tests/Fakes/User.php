<?php

namespace Ziptied\Bedrock\Tests\Fakes;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ziptied\Bedrock\Domain\Models\Concerns\Billable;

class User extends Authenticatable
{
    use Billable;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
