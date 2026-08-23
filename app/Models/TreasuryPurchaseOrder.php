<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TreasuryPurchaseOrder extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'status',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public static function createFromPurchaseOrder(PurchaseOrder $purchaseOrder): self
    {
        return self::firstOrCreate(
            ['purchase_order_id' => $purchaseOrder->id],
            ['status' => 'Pendiente']
        );
    }

    public static function searchList(array $filters)
    {
        $query = self::query()
            ->with([
                'purchaseOrder:id,folio,work_order_id,supplier_id,description,cost,payment_condition,credit_days,status',
                'purchaseOrder.workOrder:id,folio,unit_id',
                'purchaseOrder.workOrder.unit:id,econame',
                'purchaseOrder.supplier:id,name',
                'paidBy:id,name',
            ])
            ->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->get();
    }

    public function detail(): self
    {
        return $this->load([
            'purchaseOrder:id,folio,work_order_id,supplier_id,description,cost,payment_condition,credit_days,status,created_at',
            'purchaseOrder.workOrder:id,folio,unit_id',
            'purchaseOrder.workOrder.unit:id,econame',
            'purchaseOrder.supplier:id,name',
            'purchaseOrder.files:id,purchase_order_id,type,url',
            'paidBy:id,name',
        ]);
    }

    public function markAsPaid(int $userId): self
    {
        return DB::transaction(function () use ($userId) {
            $register = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($register->status === 'Pagado') {
                throw new UnprocessableEntityHttpException('La orden de compra ya fue marcada como pagada.');
            }

            $register->update([
                'status' => 'Pagado',
                'paid_by' => $userId,
                'paid_at' => now(),
            ]);

            return $register->detail();
        });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
