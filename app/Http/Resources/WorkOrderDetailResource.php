<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderDetailResource extends JsonResource
{
    public function toArray($request): array
    {

        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'unit_category' => $this->unit_category,
            'maintenance_type' => $this->maintenance_type,
            'unit_id' => $this->unit_id,
            'initial_mileage' => $this->initial_mileage,
            'opened_at' => optional($this->opened_at)->format('Y-m-d'),
            'operator_id' => $this->operator_id,
            'mechanic_id' => $this->mechanic_id,
            'failure_description' => $this->failure_description,
            'work_type' => $this->work_type,
            'status' => $this->status,
            'purchase_orders' => PurchaseOrderSummaryResource::collection( // Ordenes de Compra
                $this->whenLoaded('purchaseOrders')
            ),
            'files' => FileWorkOrderResource::collection($this->getEvidencesWorkOrders()),  //Evidencias ed ordenes de trabajo, unicamente para ordenes de compra Internas
            'started_by_user' => $this->startedBy->name,
            'started_at' => $this->started_at,
            'closed_by_user' => $this->closedBy->name,
            'closed_at' => $this->closed_at,
        ];
    }
}
