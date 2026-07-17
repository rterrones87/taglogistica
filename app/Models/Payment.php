<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Payment extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = [
        'treasury_payment_id',
        'service_id',
        'service_operator_type_id',
        'total',
        'zombie'
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function operatorType()
    {
        return $this->belongsTo(ServiceOperatorType::class, 'service_operator_type_id');
    }
}
