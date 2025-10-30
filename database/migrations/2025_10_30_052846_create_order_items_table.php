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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            /**
             * ==============================
             * Foreign Keys & Relationships
             * ==============================
             */
            $table->unsignedBigInteger('order_id')->index()->comment('Reference to orders table');
            $table->unsignedBigInteger('product_id')->nullable()->index()->comment('Reference to products table');
            $table->unsignedBigInteger('product_variant_id')->nullable()->index()->comment('Reference to product variants (if any)');

            /**
             * ==============================
             * Product Snapshot Info
             * ==============================
             * Keeping product details at the time of purchase ensures
             * order records remain accurate even if product details change later.
             */
            $table->string('product_name')->comment('Name of the product at the time of order');
            $table->string('product_sku')->nullable()->comment('Product SKU at the time of order');

            /**
             * ==============================
             * Quantity & Pricing Details
             * ==============================
             */
            $table->unsignedInteger('quantity')->default(1)->comment('Quantity purchased');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Price per unit at time of order');
            $table->decimal('total_price', 10, 2)->default(0)->comment('Quantity × Unit Price');

            /**
             * ==============================
             * Extra Metadata
             * ==============================
             * Can store product attributes (color, size, etc.)
             */
            $table->json('meta')->nullable()->comment('Additional item-specific data');

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
                ->onDelete('cascade'); // Delete order items if order is deleted

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('set null'); // Keep order item even if product deleted

            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('set null'); // Optional variant relation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
