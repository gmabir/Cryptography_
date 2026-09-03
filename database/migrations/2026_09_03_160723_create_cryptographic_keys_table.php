<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cryptographic_keys', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['rsa', 'ecc']);
            
            // Public keys are safe to store in plaintext (JSON format)
            $table->text('public_key'); 
            
            // Private keys MUST be encrypted using the Master RSA Key (JSON array of encrypted chunks)
            $table->text('encrypted_private_key'); 
            
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cryptographic_keys');
    }
};