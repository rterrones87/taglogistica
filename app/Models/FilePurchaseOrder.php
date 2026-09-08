<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FilePurchaseOrder extends Model
{
    public const TYPE_QUOTATION = 1;

    public const TYPE_EVIDENCE = 2;

    protected $fillable = [
        'purchase_order_id',
        'type',
        'url',
    ];

    protected $casts = [
        'type' => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function deleteFile(): void
    {
        Storage::disk(self::diskForType($this->type))->delete($this->url);
    }

    public static function diskForType(int $type): string
    {
        return $type === self::TYPE_QUOTATION ? 'cotizacion' : 'evidencias';
    }
}
