<?php

namespace App\Http\Resources;

use App\Models\FileWorkOrder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FileWorkOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
    
        $extension = strtolower(pathinfo($this->url, PATHINFO_EXTENSION));

        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => "Evidencia {$this->evidence_number}.{$extension}",
            'url' => Storage::disk(FileWorkOrder::DISK_FILE)->url($this->url),
            'is_image' => in_array($extension, [
                'jpg',
                'jpeg',
                'png',
                'webp',
            ], true),
        ];    
    }
}
