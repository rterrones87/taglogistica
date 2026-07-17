<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Stock extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = [
        'user_id', 
        'inventory_id',
        'last_quantity',
        'new_quantity',
        'quantity',
        'date',
        'zombie'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
