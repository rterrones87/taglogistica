<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'work_order_id' => $this->work_order_id,
            'work_order' => new WorkOrderResource($this->whenLoaded('workOrder')),
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier'),
            'description' => $this->description,
            'cost' => (float) $this->cost,
            'payment_condition' => $this->payment_condition,
            'credit_days' => $this->credit_days,
            'quotation_url' => $this->quotation_path ? Storage::url($this->quotation_path) : null,
            'evidence_url' => $this->evidence_path ? Storage::url($this->evidence_path) : null,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'closed_by' => $this->closed_by,
            'closed_by_user' => $this->whenLoaded('closedBy'),
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
        ];
    }
}
