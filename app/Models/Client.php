<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Client extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

   // protected $appends = ['formatted_destinations'];

   // protected $hidden = ['destinations'];

    protected $fillable = [
        'name',
        'company_type',
        'RFC', 
        'zip',
        'contact_name',
        'contact_email',
        'active', 
        'zombie'
    ];


    /*public function getFormattedDestinationsAttribute()
    {
        return $this->destinations
            ->groupBy('place_id')
            ->map(function ($items, $placeId) {
                $first = $items->first(); 

                return [
                    'place_id' => (int) $placeId,
                    'name' => optional($first->place)->name, 
                    'booths' => $items->map(function ($item) {
                        return [
                            'booth_id' => $item->booth_id,
                            'name' => optional($item->booth)->name
                        ];
                    })->values()
                ];
            })->values();
    }*/

}
