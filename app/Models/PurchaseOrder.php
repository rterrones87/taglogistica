<?php

namespace App\Models;

use App\Support\GeneratesAnnualFolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PurchaseOrder extends Model
{
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
        'closed_by',
        'closed_at'
    ];
    
    protected $casts = [
        'cost' => 'decimal:2',
        'closed_at' => 'datetime'
    ];

    public static function searchList(array $filters)
    {
        $query = self::query()->with(['workOrder.unit', 'supplier:id,name'])->latest('id');

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
        $data['folio'] = GeneratesAnnualFolio::for(self::class, 'OC');
        $data = self::storeFiles($data, $files);

        return self::create($data)->load(['workOrder.unit', 'supplier:id,name']);
    }

    public function detail(): self
    {
        return $this->load(['workOrder.unit', 'supplier:id,name', 'closedBy:id,name']);
    }

    public function updateRegister(array $data, array $files = []): self
    {
        if ($this->status === 'Cerrada') {
            throw new UnprocessableEntityHttpException('Una orden cerrada no puede editarse.');
        }

        $data = self::storeFiles($data, $files, $this);
        $this->update($data);

        return $this->fresh()->load(['workOrder.unit', 'supplier:id,name', 'closedBy:id,name']);
    }

    public function closeOrder(int $userId): self
    {
        if ($this->status === 'Cerrada') {
            throw new UnprocessableEntityHttpException('La orden de compra ya se encuentra cerrada.');
        }

        if (!$this->quotation_path || !$this->evidence_path) {
            throw new UnprocessableEntityHttpException('Para cerrar la OC debe adjuntar cotizacion y evidencia.');
        }

        $this->update(['status' => 'Cerrada', 'closed_by' => $userId, 'closed_at' => now()]);

        return $this->fresh()->load(['workOrder.unit', 'supplier:id,name', 'closedBy:id,name']);
    }

    private static function storeFiles(array $data, array $files, ?self $current = null): array
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

            if ($current && $current->{$definition['column']}) {
                Storage::disk('public')->delete($current->{$definition['column']});
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
    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
