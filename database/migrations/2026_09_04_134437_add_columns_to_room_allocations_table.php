<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('room_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('room_allocations', 'building_name')) {
                $table->string('building_name')->after('user_id');
            }
            if (!Schema::hasColumn('room_allocations', 'room_number')) {
                $table->string('room_number')->after('building_name');
            }
            if (!Schema::hasColumn('room_allocations', 'encrypted_notes')) {
                $table->text('encrypted_notes')->nullable()->after('room_number');
            }
            if (!Schema::hasColumn('room_allocations', 'row_mac')) {
                $table->text('row_mac')->after('encrypted_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_allocations', function (Blueprint $table) {
            //
        });
    }
};
