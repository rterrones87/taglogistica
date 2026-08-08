<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FailureReportResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit'),
            'operator_id' => $this->operator_id,
            'operator' => $this->whenLoaded('operator'),
            'mileage' => $this->mileage,
            'reported_at' => optional($this->reported_at)->format('Y-m-d'),
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'started_by' => $this->started_by,
            'started_by_user' => $this->whenLoaded('startedBy'),
            'started_at' => $this->started_at,
            'finished_by' => $this->finished_by,
            'finished_by_user' => $this->whenLoaded('finishedBy'),
            'finished_at' => $this->finished_at,
            'work_orders_count' => $this->when(isset($this->work_orders_count), $this->work_orders_count),
            'created_at' => $this->created_at,
        ];
    }
}
