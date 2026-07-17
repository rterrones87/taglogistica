<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class PartSupplierRequest extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $table = "parts_supplier_requests";

    protected $fillable = [
        'maintenance_id',
        'supplier_id',  
        'request_type',
        'description',
        'quantity',
        'cost',
        'zombie'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

}
