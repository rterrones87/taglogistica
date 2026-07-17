<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UppercaseAttributes;
use App\Traits\HasApproval;
use App\Traits\HasMexicoTimezone;
use Carbon\Carbon;

class Maintenance extends Model
{
    use HasFactory;
    use UppercaseAttributes;
    use HasApproval;
    use HasMexicoTimezone;

    protected $fillable = [
        'folio',
        'type_maintenance_id',  
        'description',
        'kms',
        'init_date',
        'unit_id',
        'maintenance_status_id',
        'zombie'
    ];

    protected $appends = [
        'approvals_map'
    ];

    protected $hidden  = ['approvals'];


    public function onApproved(Approval $approval): void {
        switch ($approval->kind) {
            case "maintenance_expenses":
                    $this->update(["maintenance_status_id" => 2]);
                    
                    // Obtener todas las solicitudes a proveedores
                    $requests = $this->partsSupplierRequests;
                    
                    // Solo crear órdenes si hay solicitudes a proveedores
                    if ($requests->isEmpty()) {
                        break;
                    }
                    
                    // Agrupar por supplier_id y calcular totales
                    $supplierTotals = $requests->groupBy('supplier_id')->map(function($items, $supplierId) {
                        $supplier = Supplier::find($supplierId);
                        $totalProducts = $items->where('request_type', 2)->sum('cost');
                        $totalServices = $items->where('request_type', 1)->sum('cost');
                        $countProducts = $items->where('request_type', 2)->count();
                        $countServices = $items->where('request_type', 1)->count();
                        
                        return [
                            'supplier_id' => $supplierId,
                            'supplier_name' => $supplier->name ?? 'Desconocido',
                            'total' => $totalProducts + $totalServices,
                            'count_products' => $countProducts,
                            'count_services' => $countServices
                        ];
                    });
                    
                    // Crear una orden de tesorería por cada proveedor
                    foreach ($supplierTotals as $data) {
                        TreasuryMaintenance::create([
                            'maintenance_id' => $this->id,
                            'user_id' => $approval->requested_by,
                            'reviewed_by' => $approval->reviewed_by,
                            'order_date' => date('Y-m-d'),
                            'total' => $data['total'],
                            'description' => sprintf(
                                '%s - %d productos, %d servicios - %s',
                                $data['supplier_name'],
                                $data['count_products'],
                                $data['count_services'],
                                $this->description
                            ),
                            'paid' => 0,
                        ]);
                    }
                break;
        }
    }

    public function onRejected(Approval $approval): void {
        switch ($approval->kind) {
            case "maintenance_expenses":
                break;
        }
    }

    public function type_maintenance()
    {
        return $this->belongsTo(TypeMaintenance::class, 'type_maintenance_id'); 
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function status()
    {
        return $this->belongsTo(MaintenanceStatus::class, 'maintenance_status_id');
    }

    public function inventoryRequests()
    {
        return $this->hasMany(InventoryRequest::class);
    }

    public function partsSupplierRequests()
    {
        return $this->hasMany(PartSupplierRequest::class);
    }

    public function evidences()
    {
        return $this->hasMany(MaintenanceEvidence::class)->where('zombie', 0);
    }

    public function snapshotForMaintenanceExpenses($supplierData) {
        $snapshot = [];
        
        foreach ($supplierData as $data) {
            $snapshot[$data['supplier_name']] = sprintf(
                'Productos: $%s | Servicios: $%s | Total: $%s',
                number_format($data['products'], 2, '.', ','),
                number_format($data['services'], 2, '.', ','),
                number_format($data['total'], 2, '.', ',')
            );
        }
        
        // Calcular conteo total de piezas del inventario
        $inventoryCount = $this->inventoryRequests()->sum('quantity');
        
        $snapshot['INVENTARIO'] = (string)$inventoryCount;
        $snapshot['DESCRIPCIÓN'] = $this->description;
        
        return $snapshot;
    }

    public function getFormattedInitDateAttribute()
    {
        return $this->formatDateLocalized($this->init_date);
    }

    private function formatDateLocalized($date)
    {
        if (!$date) return null;

        return Carbon::parse($date)->translatedFormat('l d \d\e F Y'); 
    }
}
