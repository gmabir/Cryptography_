<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomAllocation extends Model
{
    use HasFactory;

    protected $table = 'room_allocations';

    protected $fillable = [
        'user_id',
        'building_name',
        'room_number',
        'encrypted_notes',
        'row_mac',
        'allocated_by'
    ];

    /**
     * Get the student user associated with this room allocation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}