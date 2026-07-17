<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Destination extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $fillable = [
        'container_id',
        'booth_id',
        'direction'
    ];

    // Constantes para direcciones
    public const DIRECTION_OUTBOUND = 'outbound'; // Ida
    public const DIRECTION_RETURN = 'return';     // Vuelta

    // Scope para filtrar por dirección
    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeReturn($query)
    {
        return $query->where('direction', self::DIRECTION_RETURN);
    }

    public function container()
    {
        return $this->belongsTo(Container::class);
    }

    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }

}
