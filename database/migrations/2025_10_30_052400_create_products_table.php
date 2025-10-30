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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            /**
             * ----------------------------------------
             * RELATIONSHIPS
             * ----------------------------------------
             * Each product belongs to a category.
             * Using nullable to handle uncategorized products.
             */
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('set null');

            /**
             * ----------------------------------------
             * BASIC DETAILS
             * ----------------------------------------
             */
            $table->string('title');                       // Product name
            $table->string('slug')->unique();              // SEO-friendly URL identifier
            $table->text('description')->nullable();       // Long description for the product

            /**
             * ----------------------------------------
             * FEATURED IMAGE
             * ----------------------------------------
             * Main image of the product (used in product listings and previews)
             */
            $table->string('featured_image')->nullable();  // Path or URL of main image

            /**
             * ----------------------------------------
             * PRICING
             * ----------------------------------------
             * Use decimal for precise currency storage.
             */
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('discounted_price', 10, 2)->nullable(); // Optional discount

            /**
             * ----------------------------------------
             * INVENTORY
             * ----------------------------------------
             * SKU helps uniquely identify a product (used in orders/inventory tracking)
             */
            $table->string('sku')->unique()->nullable();
            $table->integer('stock')->default(0); // Track available quantity (variants can override this)

            /**
             * ----------------------------------------
             * STATUS FLAGS
             * ----------------------------------------
             * Helps filter active and featured products on frontend.
             */
            $table->boolean('is_active')->default(true);     // Product visible to customers
            $table->boolean('is_featured')->default(false);  // Featured section on homepage

            /**
             * ----------------------------------------
             * SEO & META DATA
             * ----------------------------------------
             * Store extra data like meta_title, meta_description, tags etc.
             */
            $table->json('meta')->nullable();

            /**
             * ----------------------------------------
             * TIMESTAMPS
             * ----------------------------------------
             * created_at & updated_at handled automatically by Eloquent.
             */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
