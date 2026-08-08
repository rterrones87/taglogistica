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
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit'),
            'initial_mileage' => $this->initial_mileage,
            'opened_at' => optional($this->opened_at)->format('Y-m-d'),
            'operator_id' => $this->operator_id,
            'operator' => $this->whenLoaded('operator'),
            'mechanic_id' => $this->mechanic_id,
            'mechanic' => $this->whenLoaded('mechanic'),
            'failure_description' => $this->failure_description,
            'work_type' => $this->work_type,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier'),
            'status' => $this->status,
            'purchase_orders' => PurchaseOrderResource::collection($this->whenLoaded('purchaseOrders')),
            'purchase_orders_count' => $this->when(isset($this->purchase_orders_count), $this->purchase_orders_count),
            'total_cost' => (float) ($this->purchase_orders_sum_cost ?? $this->purchaseOrders->sum('cost')),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'started_by' => $this->started_by,
            'started_by_user' => $this->whenLoaded('startedBy'),
            'started_at' => $this->started_at,
            'closed_by' => $this->closed_by,
            'closed_by_user' => $this->whenLoaded('closedBy'),
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
        ];
    }
}
