<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class InventoryRequest extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $table = "inventory_requests";

    protected $fillable = [
        'maintenance_id',
        'inventory_id',  
        'quantity',
        'zombie'
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

}
