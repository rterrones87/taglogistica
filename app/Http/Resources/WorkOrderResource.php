<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'unit_category' => $this->unit_category,
            'maintenance_type' => $this->maintenance_type,
            'unit' => $this->whenLoaded('unit'),
            'responsible' => $this->work_type === 'Externo'
                ? 'Externo'
                : "Mecanico\n".($this->mechanic?->name ?? ''),
            'status' => $this->status,
            'purchase_orders_count' => $this->when(isset($this->purchase_orders_count), $this->purchase_orders_count),
            'total_cost' => (float) ($this->purchase_orders_sum_cost ?? 0),
        ];
    }
}
