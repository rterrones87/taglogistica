<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasMexicoTimezone;
use App\Models\UnitType;

class ClientPlace extends Model
{
    use HasFactory;
    use HasMexicoTimezone;

    protected $table = 'client_places';

    protected $fillable = [
        'client_id',
        'place_id',
        'type_unit_id',
        'amount',
        'zombie',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function typeUnit()
    {
        return $this->belongsTo(UnitType::class, 'type_unit_id');
    }

    public function booths()
    {
        return $this->hasMany(ClientPlaceBooth::class);
    }
}
