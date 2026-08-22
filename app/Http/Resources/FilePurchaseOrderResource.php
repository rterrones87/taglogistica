<?php

namespace App\Http\Resources;

use App\Models\FilePurchaseOrder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FilePurchaseOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        $extension = strtolower(pathinfo($this->url, PATHINFO_EXTENSION));

        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->type === 1
                ? "Cotizacion.{$extension}"
                : "Evidencia {$this->evidence_number}.{$extension}",
            'url' => Storage::disk(FilePurchaseOrder::diskForType($this->type))->url($this->url),
            'is_image' => in_array($extension, [
                'jpg',
                'jpeg',
                'png',
                'webp',
            ], true),
        ];
    }
}
