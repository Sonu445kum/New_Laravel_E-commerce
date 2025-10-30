<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            // Unique coupon code
            $table->string('code')->unique();

            // Type: fixed amount or percentage
            $table->enum('type', ['fixed', 'percent'])->default('fixed');

            // Amount (for fixed) or percent value (for percent)
            $table->decimal('value', 10, 2);

            // Optional start / expiry times
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            // Usage limits
            $table->integer('usage_limit')->nullable(); // null => unlimited
            $table->integer('used_count')->default(0);

            // Minimum order value to apply coupon
            $table->decimal('min_order_amount', 10, 2)->nullable();

            // Where coupon applies (store as JSON: e.g. {"type":"category","ids":[1,2]} or plain array of ids)
            $table->json('applies_to')->nullable();

            // Active flag
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Helpful indexes
            $table->index(['is_active']);
            $table->index(['starts_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
