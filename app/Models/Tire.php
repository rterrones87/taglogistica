<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasApproval;
use App\Traits\HasMexicoTimezone;

class Tire extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasApproval;
    use HasMexicoTimezone;

    protected $fillable = [
        'unit_id',
        'inventory_id',
        'serial',
        'position',
        'date',
        'zombie'
    ];

    protected $appends = [
        'approvals_map'
    ];

    protected $hidden = ['approvals'];

    public function onApproved(Approval $approval): void {
        switch ($approval->kind) {
            case "tire_expenses":
                // Obtener inventario
                $inventory = Inventory::find($this->inventory_id);
                
                // Registrar movimiento en stocks
                Stock::create([
                    'user_id' => $approval->requested_by ?? 1,
                    'inventory_id' => $this->inventory_id,
                    'last_quantity' => $inventory->quantity,
                    'quantity' => -1,  // Descuento de 1 unidad
                    'new_quantity' => $inventory->quantity - 1,
                    'date' => now()->format('Y-m-d')
                ]);
                
                // Actualizar cantidad en inventario
                $inventory->decrement('quantity', 1);
                break;
        }
    }

    public function onRejected(Approval $approval): void {
        switch ($approval->kind) {
            case "tire_expenses":
                    $this->update(['zombie' => 1]);
                break;
        }
    }


    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function snapshotForTireExpenses($tire) {
        return [
            "UNIDAD" => $tire->unit->econame . ", " . $tire->position,
            "LLANTA" => $tire->inventory->name,
            "MARCA" => $tire->inventory->brand,
            "SERIAL" => $tire->serial
        ];
    }
}
