<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Unit extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = [
        'type', 
        'econame', 
        'brand', 
        'model', 
        'llantas', 
        'trailer', 
        'TAG', 
        'active', 
        'zombie'
    ];

    public static function searchList(array $filters)
    {
        $query = self::query()->where('active', 1)->where('zombie', 0)->orderBy('econame');

        if (!empty($filters['columns'])) {
            return $query->get($filters['columns']);
        }

        return $query->get();
    }
}
