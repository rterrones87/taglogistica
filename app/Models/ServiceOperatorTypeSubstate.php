<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOperatorTypeSubstate extends Model
{
    use HasFactory;

    protected $table = 'service_operator_type_substates';

    protected $fillable = [
        'service_operator_type_id',
        'substate_id',
    ];

    public function operatorType()
    {
        return $this->belongsTo(ServiceOperatorType::class, 'service_operator_type_id');
    }
}
