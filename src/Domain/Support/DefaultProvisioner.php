<?php

namespace Ziptied\Bedrock\Domain\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Ziptied\Bedrock\Domain\Contracts\Provisioner;

class DefaultProvisioner implements Provisioner
{
    public function __construct(private readonly string $billableModel)
    {
    }

    public function provision(array $resolvePayload): Model
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->billableModel;

        $existing = $modelClass::query()->first();
        if ($existing) {
            return $existing;
        }

        /** @var Model $model */
        $model = new $modelClass();
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            throw new \RuntimeException("Billable table [{$table}] not found. Ensure migrations run before provisioning.");
        }

        $attributes = [];

        if (Schema::hasColumn($table, 'name')) {
            $attributes['name'] = $resolvePayload['purchaserTenantName']
                ?? $resolvePayload['purchaserEmail']
                ?? 'Azure Marketplace Subscriber';
        }

        if (Schema::hasColumn($table, 'email') && isset($resolvePayload['purchaserEmail'])) {
            $attributes['email'] = $resolvePayload['purchaserEmail'];
        }

        if (Schema::hasColumn($table, 'password')) {
            $attributes['password'] = Hash::make(Str::random(40));
        }

        $model->forceFill($attributes);
        $model->save();

        return $model;
    }
}
