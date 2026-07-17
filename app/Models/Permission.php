<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Permission extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = ['name'];

    /**
     * Los roles que tienen este permiso
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
