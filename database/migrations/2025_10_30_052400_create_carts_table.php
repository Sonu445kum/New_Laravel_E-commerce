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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // For logged in user
            $table->string('session_id')->nullable()->index(); // For guest users
            $table->decimal('subtotal', 10, 2)->default(0); // Cart total before discounts/taxes
            $table->timestamps();

            // Foreign key constraint (optional if guest cart is supported)
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null'); // user delete hone par cart null ho jaye
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
