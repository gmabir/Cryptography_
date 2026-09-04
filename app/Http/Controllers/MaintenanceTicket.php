<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceTicket extends Model
{
    use HasFactory;

    protected $table = 'maintenance_tickets';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'response'
    ];

    /**
     * Get the student user who submitted this ticket.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}