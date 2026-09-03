<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RoomAllocation extends Model {
    protected $fillable = ['user_id', 'allocated_by', 'encrypted_building_name', 'encrypted_room_number', 'encrypted_notes', 'row_mac'];
}