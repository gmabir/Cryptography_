<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Plaintext for dashboard filtering (open, in_progress, resolved)
            $table->string('status')->default('open'); 
            
            // Student's initial complaint
            $table->text('encrypted_title');
            $table->text('encrypted_description');
            
            // Authority's response
            $table->text('encrypted_response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('cascade');
            
            // Data Integrity MAC
            $table->string('row_mac');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};