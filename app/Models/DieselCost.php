<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class DieselCost extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $table = 'diesel_cost';

    protected $fillable = [
        'price',
        'active',
        'zombie'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

}
