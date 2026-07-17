<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\UppercaseAttributes;

class User extends Authenticatable
{
use HasApiTokens, HasFactory, Notifiable;
    use UppercaseAttributes;
    use \App\Traits\HasMexicoTimezone;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'picture',
        'active', 
        'fcm_token',
        'zombie'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class)->with('permissions');
    }

    /**
     * Verifica si el usuario tiene un permiso específico
     */
    public function hasPermission($permission)
    {
        if (!$this->role) {
            return false;
        }
        return $this->role->permissions()->where('name', $permission)->exists();
    }

    /**
     * Verifica si el usuario tiene al menos uno de los permisos dados
     */
    public function hasAnyPermission(array $permissions)
    {
        if (!$this->role) {
            return false;
        }
        return $this->role->permissions()->whereIn('name', $permissions)->exists();
    }

    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }

    
}
