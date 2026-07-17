<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Diesel extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;
    
    protected $table = 'diesel';

    protected $fillable = [
        'service_id',
        'service_operator_id',
        'amount',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

}
