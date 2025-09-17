<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('marketplace_operations')) {
            Schema::create('marketplace_operations', function (Blueprint $table) {
                $table->id();
                $table->uuid('azure_operation_id')->unique();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->string('action');
                $table->string('status');
                $table->json('payload');
                $table->string('ack_status')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('marketplace_usage_events')) {
            Schema::create('marketplace_usage_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscription_id')->nullable();
                $table->uuid('azure_subscription_id');
                $table->string('dimension');
                $table->decimal('quantity', 12, 4);
                $table->timestamp('effective_start_time');
                $table->string('plan_id')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->json('response')->nullable();
                $table->timestamps();

                $table->index(['subscription_id', 'sent_at']);
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_usage_events');
        Schema::dropIfExists('marketplace_operations');
    }
};
