<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        $evidenceFiles = $this->whenLoaded('files', function () {
            return $this->files
                ->where('type', 2)
                ->values()
                ->each(function ($file, $index) {
                    $file->setAttribute('evidence_number', $index + 1);
                });
        });

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
            'quotation_file' => new FilePurchaseOrderResource(
                $this->whenLoaded('files', fn () => $this->files->firstWhere('type', 1))
            ),
            'evidence_files' => FilePurchaseOrderResource::collection(
                $evidenceFiles
            ),
            'status' => $this->status,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator'),
            'approvals_map' => $this->approvals_map,
            'treasury_accepted_by' => $this->treasury_accepted_by,
            'treasury_accepted_by_user' => $this->whenLoaded('treasuryAcceptedBy'),
            'treasury_accepted_at' => $this->treasury_accepted_at,
            'created_at' => $this->created_at,
        ];
    }
}
