<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Inventory extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = [
        'name', 
        'brand',
        'presentation',  
        'quantity',
        'zombie'
    ];
    
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
