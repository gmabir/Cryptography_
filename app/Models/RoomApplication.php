<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RoomApplication extends Model {
    protected $fillable = ['user_id', 'status', 'encrypted_preferences', 'encrypted_medical_needs', 'row_mac'];
}