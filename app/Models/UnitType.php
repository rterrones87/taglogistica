<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;

class UnitType extends Model
{
    use HasFactory;
    use UppercaseAttributes;

    protected $fillable = [
        'name',
        'zombie',
    ];
}
