<?php

namespace App\Models;

use App\Support\GeneratesAnnualFolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class WorkOrder extends Model
{
    protected $fillable = [
        'folio',
        'failure_report_id',
        'unit_category',
        'maintenance_type',
        'unit_id',
        'initial_mileage',
        'opened_at',
        'operator_id',
        'mechanic_id',
        'failure_description',
        'work_type',
        'supplier_id',
        'status',
        'created_by',
        'started_by',
        'started_at',
        'closed_by',
        'closed_at'
    ];
    protected $casts = [
        'opened_at' => 'date:Y-m-d',
        'started_at' => 'datetime',
        'closed_at' => 'datetime'
    ];

    public static function searchList(array $filters)
    {
        $query = self::query()
            ->with(['unit', 'operator:id,name', 'mechanic:id,name', 'supplier:id,name'])
            ->withCount('purchaseOrders')
            ->withSum('purchaseOrders', 'cost')
            ->latest('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('folio', 'like', "%{$search}%")
                    ->orWhereHas('unit', function ($unitQuery) use ($search) {
                        $unitQuery->where('econame', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['only_open'])) {
            $query->where('status', '!=', 'Cerrado');
        }

        return $query->get();
    }

    public static function createRegister(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $data['folio'] = GeneratesAnnualFolio::for(self::class, 'OT');
            $order = self::create($data);

            return $order->load(self::detailRelations());
        });
    }

    public function detail(): self
    {
        return $this->load(self::detailRelations());
    }

    public function updateRegister(array $data): self
    {
        if ($this->status === 'Cerrado') {
            throw new UnprocessableEntityHttpException('Una orden cerrada no puede editarse.');
        }

        $this->update($data);

        return $this->fresh()->load(self::detailRelations());
    }

    public function startOrder(int $userId): self
    {
        if ($this->status !== 'Abierto') {
            throw new UnprocessableEntityHttpException('Solo una orden Abierta puede iniciar el trabajo.');
        }

        $this->update(['status' => 'En Proceso', 'started_by' => $userId, 'started_at' => now()]);

        return $this->fresh()->load(self::detailRelations());
    }

    public function closeOrder(int $userId): self
    {
        if ($this->status !== 'En Proceso') {
            throw new UnprocessableEntityHttpException('La orden debe estar En Proceso para cerrarse.');
        }

        $this->update(['status' => 'Cerrado', 'closed_by' => $userId, 'closed_at' => now()]);

        return $this->fresh()->load(self::detailRelations());
    }

    private static function detailRelations(): array
    {
        return ['unit', 'operator:id,name', 'mechanic:id,name', 'supplier:id,name', 'creator:id,name', 'startedBy:id,name', 'closedBy:id,name', 'purchaseOrders.supplier:id,name'];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }
    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
    public function failureReport()
    {
        return $this->belongsTo(FailureReport::class, 'failure_report_id');
    }
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'work_order_id');
    }
}
