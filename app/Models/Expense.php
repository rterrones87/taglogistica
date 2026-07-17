<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Expense extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = [
        'service_id',
        'type',
        'concept',
        'cost',  
        'zombie'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    
}
