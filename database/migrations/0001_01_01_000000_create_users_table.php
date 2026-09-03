<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Role-Based Access Control (RBAC)
            $table->enum('role', ['student', 'warden', 'admin'])->default('student');

            // Deterministic hash used exclusively for database lookups during login
            $table->string('login_hash')->unique()->comment('MAC of username for authentication lookups');

            // RSA Encrypted User Profile Information (No plaintext allowed)
            $table->text('encrypted_username');
            $table->text('encrypted_email');
            $table->text('encrypted_phone')->nullable();
            $table->text('encrypted_student_id')->nullable();
            $table->text('encrypted_address')->nullable();
            $table->text('encrypted_emergency_contact')->nullable();

            // Custom Salt + HMAC Password System
            $table->string('password_salt');
            $table->string('hashed_password');
            
            // Encrypted 2FA Secret
            $table->text('encrypted_two_factor_secret')->nullable();

            // Data Integrity MAC to detect unauthorized row modifications
            $table->string('row_mac')->comment('Integrity check for the entire row');

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('login_hash')->primary(); // Replaced 'email' with 'login_hash'
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};