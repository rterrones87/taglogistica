<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOperatorTypeRate extends Model
{
    use HasFactory;

    protected $table = 'service_operator_type_rates';

    protected $fillable = [
        'service_operator_type_id',
        'name',
        'amount',
        'zombie',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function operatorType()
    {
        return $this->belongsTo(ServiceOperatorType::class, 'service_operator_type_id');
    }
}
