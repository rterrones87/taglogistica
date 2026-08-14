<?php

namespace App\Models;

use App\Support\GeneratesAnnualFolio;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PurchaseOrder extends Model
{
    use HasApproval;

    protected $fillable = [
        'folio',
        'work_order_id',
        'supplier_id',
        'description',
        'cost',
        'payment_condition',
        'credit_days',
        'quotation_path',
        'evidence_path',
        'status',
        'created_by',
        'treasury_accepted_by',
        'treasury_accepted_at'
    ];
    
    protected $casts = [
        'cost' => 'decimal:2',
        'treasury_accepted_at' => 'datetime'
    ];

    public static function searchList(array $filters)
    {
        $query = self::query()->with(self::detailRelations())->latest('id');

        if (!empty($filters['work_order_id'])) {
            $query->where('work_order_id', $filters['work_order_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public static function createRegister(array $data, array $files = []): self
    {
        return DB::transaction(function () use ($data, $files) {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($data['work_order_id']);

            if ($workOrder->status !== 'En Proceso') {
                throw new UnprocessableEntityHttpException('Solo se pueden crear ordenes de compra para una OT En Proceso.');
            }

            $data['folio'] = GeneratesAnnualFolio::for(self::class, 'OC');
            $data['status'] = 'Pendiente';
            $data = self::storeFiles($data, $files);

            $order = self::create($data);
            $order->requestApproval('purchase_order', $data['created_by'], [
                'Folio OC' => $order->folio,
                'Folio OT' => $workOrder->folio,
                'Costo' => number_format((float) $order->cost, 2, '.', ','),
                'Descripcion' => $order->description,
            ]);

            return $order->load(self::detailRelations());
        });
    }

    public function detail(): self
    {
        return $this->load(self::detailRelations());
    }

    public function updatePaymentCondition(array $data): self
    {
        $this->update([
            'payment_condition' => $data['payment_condition'] ?? null,
            'credit_days' => ($data['payment_condition'] ?? null) === 'Credito'
                ? $data['credit_days']
                : null,
        ]);

        return $this->fresh()->load(self::detailRelations());
    }

    public function onApproved(Approval $approval): void
    {
        if ($approval->kind === 'purchase_order') {
            $this->update(['status' => 'Aprobada']);
        }
    }

    public function onRejected(Approval $approval): void
    {
        if ($approval->kind === 'purchase_order') {
            $this->update(['status' => 'Rechazada']);
        }
    }

    public static function searchTreasuryList(array $filters = [])
    {
        $query = self::query()
            ->with(self::detailRelations())
            ->where('status', 'Aprobada')
            ->latest('id');

        if (array_key_exists('accepted', $filters) && $filters['accepted'] !== null) {
            $filters['accepted']
                ? $query->whereNotNull('treasury_accepted_at')
                : $query->whereNull('treasury_accepted_at');
        }

        return $query->get();
    }

    public function acceptForTreasury(int $userId): self
    {
        if ($this->status !== 'Aprobada') {
            throw new UnprocessableEntityHttpException('Tesoreria solo puede aceptar ordenes de compra aprobadas.');
        }

        if (!$this->treasury_accepted_at) {
            $this->update([
                'treasury_accepted_by' => $userId,
                'treasury_accepted_at' => now(),
            ]);
        }

        return $this->fresh()->load(self::detailRelations());
    }

    private static function storeFiles(array $data, array $files): array
    {
        $definitions = [
            'quotation' => ['column' => 'quotation_path', 'folder' => 'maintenance/quotations'],
            'evidence' => ['column' => 'evidence_path', 'folder' => 'maintenance/evidence'],
        ];

        foreach ($definitions as $input => $definition) {
            $file = $files[$input] ?? null;

            if (!$file instanceof UploadedFile) {
                continue;
            }

            $data[$definition['column']] = $file->store($definition['folder'], 'public');
        }

        return $data;
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function treasuryAcceptedBy()
    {
        return $this->belongsTo(User::class, 'treasury_accepted_by');
    }

    private static function detailRelations(): array
    {
        return [
            'workOrder.unit',
            'supplier:id,name',
            'creator:id,name',
            'treasuryAcceptedBy:id,name',
            'approvals',
        ];
    }
}
