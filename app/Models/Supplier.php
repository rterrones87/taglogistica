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

    public static function searchList(array $filters)
    {
        $query = self::query()->where('active', 1)->where('zombie', 0)->orderBy('name');

        return $query->get($filters['columns'] ?? ['*']);
    }
}
