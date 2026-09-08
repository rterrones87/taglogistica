<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TreasuryPurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        $order = $this->whenLoaded('purchaseOrder');
        $files = $order && $order->relationLoaded('files') ? $order->files : collect();
        $evidences = $files->where('type', 2)->values()->each(
            fn ($file, $index) => $file->setAttribute('evidence_number', $index + 1)
        );

        return [
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'paid_at' => $this->paid_at,
            'paid_by_user' => $this->whenLoaded('paidBy'),
            'purchase_order' => $order ? [
                'id' => $order->id,
                'folio' => $order->folio,
                'description' => $order->description,
                'cost' => (float) $order->cost,
                'payment_condition' => $order->payment_condition,
                'credit_days' => $order->credit_days,
                'supplier' => $order->relationLoaded('supplier') ? $order->supplier : null,
                'work_order' => $order->relationLoaded('workOrder') ? [
                    'id' => $order->workOrder?->id,
                    'folio' => $order->workOrder?->folio,
                    'unit' => $order->workOrder?->relationLoaded('unit') ? $order->workOrder?->unit : null,
                ] : null,
                'quotation_file' => $order->relationLoaded('files')
                    ? new FilePurchaseOrderResource($files->firstWhere('type', 1))
                    : null,
                'evidence_files' => $order->relationLoaded('files')
                    ? FilePurchaseOrderResource::collection($evidences)
                    : [],
            ] : null,
        ];
    }
}
