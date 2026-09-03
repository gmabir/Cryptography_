<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'user_id', 
        'status', 
        'encrypted_title', 
        'encrypted_description', 
        'encrypted_response', 
        'responded_by', 
        'row_mac'
    ];
}