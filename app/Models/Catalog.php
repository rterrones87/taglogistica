<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Catalog extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = [
        'type_collection', 
        'title',  
        'zombie'
    ];
}
