<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderSummaryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'name' => $this->supplier->name,
            ]),
            'description' => $this->description,
            'cost' => (float) $this->cost,
            'status' => $this->status,
        ];
    }
}
