<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOperatorType extends Model
{
    use HasFactory;

    protected $table = 'service_operator_types';

    protected $fillable = [
        'name',
        'code',
        'type_operation',
        'is_main',
        'zombie',
    ];

    public function substates()
    {
        return $this->hasMany(ServiceOperatorTypeSubstate::class, 'service_operator_type_id');
    }

    public function serviceOperators()
    {
        return $this->hasMany(ServiceOperator::class, 'service_operator_type_id');
    }

    public function rates()
    {
        return $this->hasMany(ServiceOperatorTypeRate::class, 'service_operator_type_id')
            ->where('zombie', 0)
            ->orderBy('name');
    }
}
