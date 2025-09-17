<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('name')->default('default');
                $table->string('stripe_id')->nullable();
                $table->string('stripe_status')->nullable();
                $table->string('stripe_price')->nullable();
                $table->integer('quantity')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('provider')->default('azure');
                $table->uuid('azure_subscription_id')->nullable();
                $table->string('azure_offer_id')->nullable();
                $table->string('azure_plan_id')->nullable();
                $table->string('azure_status')->nullable();
                $table->integer('azure_quantity')->nullable();
                $table->timestamp('azure_term_start')->nullable();
                $table->timestamp('azure_term_end')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('azure_subscription_id');
                $table->unique(['provider', 'azure_subscription_id']);
            });
        }

        if (!Schema::hasTable('subscription_items')) {
            Schema::create('subscription_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscription_id');
                $table->string('stripe_id')->nullable();
                $table->string('stripe_product')->nullable();
                $table->string('stripe_price')->nullable();
                $table->integer('quantity')->nullable();
                $table->timestamps();

                $table->index('subscription_id');
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
    }
};
