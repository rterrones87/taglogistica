<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasMexicoTimezone;

class ClientPlaceBooth extends Model
{
    use HasFactory;
    use HasMexicoTimezone;

    protected $table = 'client_place_booths';

    protected $fillable = [
        'client_place_id',
        'booth_id',
        'direction',
    ];

    public function clientPlace()
    {
        return $this->belongsTo(ClientPlace::class);
    }

    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }
}
