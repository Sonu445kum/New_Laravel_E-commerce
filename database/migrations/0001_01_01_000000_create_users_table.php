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

            $table->string('name'); // Full name of the user
            $table->string('email')->unique(); // Unique email for login

            $table->timestamp('email_verified_at')->nullable(); // Email verification timestamp
            $table->string('password'); // Hashed password

            $table->string('phone', 20)->nullable()->index(); // Optional phone number + indexed for lookup

            // Role-based access system: customer / admin / vendor
            $table->enum('role', ['customer', 'admin', 'vendor'])->default('customer')->index();

            // Used for blocking/unblocking users (instead of deleting)
            $table->boolean('is_blocked')->default(false)->index();

            // Used for "remember me" functionality
            $table->rememberToken();

            // Timestamps (created_at, updated_at)
            $table->timestamps();

            // Soft delete (safe user removal)
            $table->softDeletes();

            // Additional optional indexes for speed
            $table->index(['email_verified_at']);
        });

        /**
         * ===============================
         * PASSWORD RESET TOKENS TABLE
         * ===============================
         * Stores temporary tokens for password reset links
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // Email serves as primary key
            $table->string('token'); // Reset token (hashed)
            $table->timestamp('created_at')->nullable(); // When token was generated

            // Optional index for cleanup queries
            $table->index('created_at');
        });

        /**
         * ===============================
         * SESSIONS TABLE
         * ===============================
         * Stores session information for logged-in users
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // Session ID (auto-generated)

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->index(); // User linked to this session

            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6 address
            $table->text('user_agent')->nullable(); // Browser/device info

            $table->longText('payload'); // Encrypted session data
            $table->integer('last_activity')->index(); // Last activity timestamp
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop child tables first to prevent FK constraint issues
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
