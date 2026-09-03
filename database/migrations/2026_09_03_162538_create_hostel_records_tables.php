<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table for Student Room Applications
        Schema::create('room_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Plaintext for basic filtering, but details are fully encrypted via ECC
            $table->string('status')->default('pending'); 
            
            $table->text('encrypted_preferences'); // e.g., "Single room, AC, quiet block"
            $table->text('encrypted_medical_needs')->nullable();
            
            // Data Integrity MAC
            $table->string('row_mac');
            $table->timestamps();
        });

        // Table for Hostel Authority Room Allocations
        Schema::create('room_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('allocated_by')->constrained('users')->onDelete('cascade'); // Warden ID
            
            $table->text('encrypted_building_name');
            $table->text('encrypted_room_number');
            $table->text('encrypted_notes')->nullable();
            
            // Data Integrity MAC
            $table->string('row_mac');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_allocations');
        Schema::dropIfExists('room_applications');
    }
};