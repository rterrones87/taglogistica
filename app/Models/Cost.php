<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasMexicoTimezone;

class Cost extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasMexicoTimezone;

    protected $appends = ['formatted_initial_costs','formatted_info', 'formatted_destinations', 'formatted_extras_costs'];

    protected $fillable = [
        'service_id',
        'waybill',
        'booth_costs',  
        'travel_cost',
        'zombie'
    ];

    protected $casts = [
        'booth_costs' => 'decimal:2',
        'travel_cost' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getFormattedInitialCostsAttribute()
    {
        return Expense::where('service_id', $this->service_id)->where('type', 'INIT')->get();
    }

    public function getFormattedExtrasCostsAttribute()
    {
        return Expense::where('service_id', $this->service_id)->where('type', 'EXTRAS')->get();
    }

    public function getFormattedInfoAttribute()
    {
        $list = [1 => 'Importación', 2 => 'Exportación', 3 => 'Carga Suelta'];
        $service = Service::find($this->service_id);
        $containers = Container::where('service_id', $this->service_id)->get();
        $placeNames = $containers->map(function ($container) {
            return $container->place->name ?? null;
        })->filter()->unique()->values();

        return $list[$service->type_operation] . ' | ' . $placeNames->implode(', ');
    }

     public function getFormattedDestinationsAttribute()
    {
        $svc = $this->relationLoaded('service')
            ? $this->service
            : $this->service()->with('containers.place','containers.destinations.booth')->first();

        if (!$svc) return [];

        return $svc->containers
            ->groupBy('place_id')
            ->map(fn($cs,$pid) => [
                'place_id'     => (int) $pid,
                'name'         => optional($cs->first()->place)->name,
                'container_id' => (int) $cs->first()->id,
                
                // NUEVO: Separar casetas por dirección
                'booths_outbound' => $cs->flatMap->destinations
                    ->where('direction', 'outbound')
                    ->map(fn($d) => [
                        'booth_id' => (int)$d->booth_id,
                        'name'     => optional($d->booth)->name,
                        'cost'     => $d->booth->cost ?? 0
                    ])
                    ->unique(fn($item) => $item['booth_id'])
                    ->values()
                    ->toArray(),
                
                'booths_return' => $cs->flatMap->destinations
                    ->where('direction', 'return')
                    ->map(fn($d) => [
                        'booth_id' => (int)$d->booth_id,
                        'name'     => optional($d->booth)->name,
                        'cost'     => $d->booth->cost ?? 0
                    ])
                    ->unique(fn($item) => $item['booth_id'])
                    ->values()
                    ->toArray(),
                
                // MANTENER: booths (por compatibilidad con código existente)
                // Retorna todas las casetas (ida + vuelta) sin filtrar duplicados
                'booths' => $cs->flatMap->destinations
                    ->map(fn($d) => [
                        'booth_id' => (int)$d->booth_id,
                        'name'     => optional($d->booth)->name,
                        'cost'     => $d->booth->cost ?? 0,
                        'direction' => $d->direction
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->values()
            ->toArray();
    }
}
