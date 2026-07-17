<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Supplier extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = [
        'type',
        'name', 
        'taxID', 
        'company_type', 
        'RFC', 
        'zip', 
        'active', 
        'invoice_required',
        'zombie'
    ];
}
