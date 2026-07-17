<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasMexicoTimezone;

class Evidence extends Model
{
    use HasFactory;
    use HasMexicoTimezone;
    
    protected $table = 'evidences';
    
    protected $fillable = [
        'service_id',  
        'file_name',
        'zombie'
    ];
}
