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
        /**
         * ===============================
         * USERS TABLE
         * ===============================
         * Stores all registered users (customers, admins, vendors)
         */
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->string('name'); // Full name
            $table->string('email')->unique(); // Unique email for login
            $table->timestamp('email_verified_at')->nullable(); // Email verification timestamp
            $table->string('password'); // Hashed password
            $table->string('phone', 20)->nullable()->index(); // Optional phone number

            // Role-based access: customer / admin / vendor
            $table->enum('role', ['customer', 'admin', 'vendor'])
                  ->default('customer')
                  ->index();

            // Custom flags
            $table->boolean('is_admin')->default(false)->index();   // ✅ supports admin middleware
            $table->boolean('is_active')->default(true)->index();   // ✅ account status (for active/inactive)
            $table->boolean('is_blocked')->default(false)->index(); // ✅ soft blocking (ban system)

            // Remember token for "remember me" functionality
            $table->rememberToken();

            // Default timestamps
            $table->timestamps();

            // Soft delete (safe user removal)
            $table->softDeletes();

            // Optional index for analytics/performance
            $table->index(['email_verified_at']);
        });

        /**
         * ===============================
         * PASSWORD RESET TOKENS TABLE
         * ===============================
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->index('created_at');
        });

        /**
         * ===============================
         * SESSIONS TABLE
         * ===============================
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->index();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
