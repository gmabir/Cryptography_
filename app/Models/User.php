<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role',
        'login_hash',
        'encrypted_username',
        'encrypted_email',
        'encrypted_phone',
        'encrypted_student_id',
        'encrypted_address',
        'encrypted_emergency_contact',
        'password_salt',
        'hashed_password',
        'encrypted_two_factor_secret',
        'row_mac',
        'profile_photo',
        'is_approved'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_salt',
        'hashed_password',
        'encrypted_two_factor_secret',
        'row_mac',
        'remember_token',
    ];

    /**
     * Override Laravel's default password field.
     * Tells the framework to look at 'hashed_password' instead of 'password'.
     */
    public function getAuthPassword()
    {
        return $this->hashed_password;
    }

    /**
     * -----------------------------------------------------------------
     * Role-Based Access Control (RBAC) Helpers
     * -----------------------------------------------------------------
     */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isWarden(): bool
    {
        return $this->role === 'warden';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}