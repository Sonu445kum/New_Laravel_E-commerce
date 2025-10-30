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

            $table->string('phone')->nullable(); // Optional phone number for contact
            $table->enum('role', ['customer', 'admin', 'vendor'])->default('customer'); 
            // Role-based access system

            $table->boolean('is_blocked')->default(false); 
            // Used for blocking/unblocking users (instead of deleting)

            $table->rememberToken(); // Required for Laravel "remember me" feature
            $table->timestamps(); // created_at & updated_at timestamps

            $table->softDeletes(); // Allows safe user deletion (can restore later)

            // Indexes to improve query performance
            $table->index(['role', 'is_blocked']);
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
        });

        /**
         * ===============================
         * SESSIONS TABLE
         * ===============================
         * Stores session information for logged-in users
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // Session ID (auto-generated)
            $table->foreignId('user_id')->nullable()->index(); 
            // Optional: user linked to this session

            $table->string('ip_address', 45)->nullable(); // IP address (supports IPv6)
            $table->text('user_agent')->nullable(); // Browser/device info

            $table->longText('payload'); // Encrypted session data
            $table->integer('last_activity')->index(); // Last activity timestamp

            // 🔗 Optional: Add foreign key for better data integrity
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop child tables first to prevent foreign key constraint issues
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
