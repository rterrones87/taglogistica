<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOperator extends Model
{
    use HasFactory;

    protected $table = 'service_operators';

    protected $fillable = [
        'service_id',
        'operator_id',
        'unit_id',
        'service_operator_type_id',
        'rate_id',
        'amount_bonus',
        'bonus_paid',
        'zombie',
    ];

    protected $casts = [
        'amount_bonus' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function type()
    {
        return $this->belongsTo(ServiceOperatorType::class, 'service_operator_type_id');
    }

    public function rate()
    {
        return $this->belongsTo(ServiceOperatorTypeRate::class, 'rate_id');
    }
}
