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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            /**
             * ==============================
             * Foreign Key Relationships
             * ==============================
             */
            $table->unsignedBigInteger('order_id')->index()->comment('Reference to orders table');

            /**
             * ==============================
             * Transaction Details
             * ==============================
             */
            $table->string('transaction_id')->nullable()->unique()->comment('Unique transaction reference from gateway');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])
                ->default('pending')
                ->comment('Payment status: pending, success, failed, refunded');
            $table->string('method')->comment('Payment method: stripe, razorpay, cod, bank_transfer');

            /**
             * ==============================
             * Financial Information
             * ==============================
             */
            $table->decimal('amount', 10, 2)->default(0)->comment('Total payment amount');
            $table->json('meta')->nullable()->comment('Gateway or API response data (JSON)');

            /**
             * ==============================
             * System Columns
             * ==============================
             */
            $table->timestamps();

            /**
             * ==============================
             * Foreign Key Constraints
             * ==============================
             */
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade'); // Delete payment if order deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
