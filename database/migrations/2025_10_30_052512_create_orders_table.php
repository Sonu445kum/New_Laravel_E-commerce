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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            /**
             * ==============================
             * Basic Order Information
             * ==============================
             */
            $table->uuid('uuid')->unique()->comment('Public-safe unique identifier (for API use)');
            $table->string('order_number')->unique()->comment('Readable unique order reference number like ORD-XXXX');

            /**
             * ==============================
             * Foreign Keys & Relationships
             * ==============================
             */
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->cascadeOnUpdate()
                  ->comment('Customer who placed the order');

            $table->foreignId('address_id')
                  ->nullable()
                  ->constrained('addresses')
                  ->nullOnDelete()
                  ->cascadeOnUpdate()
                  ->comment('Shipping or billing address');

            /**
             * ==============================
             * Financial Details
             * ==============================
             */
            $table->decimal('subtotal', 12, 2)->default(0)->comment('Subtotal before tax, shipping, and discounts');
            $table->decimal('shipping', 12, 2)->default(0)->comment('Shipping charge');
            $table->decimal('tax', 12, 2)->default(0)->comment('Tax amount');
            $table->decimal('discount', 12, 2)->default(0)->comment('Discount amount');
            $table->decimal('total', 12, 2)->default(0)->comment('Final payable total');

            /**
             * ==============================
             * Order Status & Meta Info
             * ==============================
             */
            $table->enum('status', [
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded'
            ])->default('pending')->index()->comment('Current status of the order');

            $table->string('payment_status')->default('unpaid')->comment('Payment status: unpaid, paid, refunded, failed');
            $table->string('payment_method')->nullable()->comment('Payment gateway or method used');

            $table->text('notes')->nullable()->comment('Customer or admin notes');
            $table->json('meta')->nullable()->comment('Extra metadata like coupon, delivery info, etc.');

            /**
             * ==============================
             * System Columns
             * ==============================
             */
            $table->timestamps();
            $table->softDeletes(); // keep history after soft deletion
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
