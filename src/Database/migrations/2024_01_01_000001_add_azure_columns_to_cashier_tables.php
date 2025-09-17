<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $columns = Schema::getColumnListing('subscriptions');

            if (!in_array('provider', $columns, true)) {
                $table->string('provider')->default('azure')->after('ends_at');
            }
            if (!in_array('azure_subscription_id', $columns, true)) {
                $table->uuid('azure_subscription_id')->nullable()->after('provider');
            }
            if (!in_array('azure_offer_id', $columns, true)) {
                $table->string('azure_offer_id')->nullable()->after('azure_subscription_id');
            }
            if (!in_array('azure_plan_id', $columns, true)) {
                $table->string('azure_plan_id')->nullable()->after('azure_offer_id');
            }
            if (!in_array('azure_status', $columns, true)) {
                $table->string('azure_status')->nullable()->after('azure_plan_id');
            }
            if (!in_array('azure_quantity', $columns, true)) {
                $table->integer('azure_quantity')->nullable()->after('azure_status');
            }
            if (!in_array('azure_term_start', $columns, true)) {
                $table->timestamp('azure_term_start')->nullable()->after('azure_quantity');
            }
            if (!in_array('azure_term_end', $columns, true)) {
                $table->timestamp('azure_term_end')->nullable()->after('azure_term_start');
            }

            $providerColumn = config('azure_marketplace.cashier_compat.provider_column', 'provider');
            if ($providerColumn !== 'provider' && !in_array($providerColumn, $columns, true)) {
                $table->string($providerColumn)->default('azure')->after('ends_at');
            }
        });
    }

    public function down(): void
    {
        // Columns intentionally left in place to preserve data on rollback.
    }
};
