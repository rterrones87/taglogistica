<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasMexicoTimezone;

class SubstateHistory extends Model
{
    use HasFactory;
    use HasMexicoTimezone;

    protected $table = 'substate_history';

    protected $fillable = [
        'service_id',
        'operator_id',
        'substate_id',
        'state_id'
    ];

    /**
     * Relación con el servicio
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relación con el operador (chofer)
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Relación con el subestado
     */
    public function substate()
    {
        return $this->belongsTo(Substate::class);
    }

    /**
     * Scope para filtrar por servicio
     */
    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }
}
