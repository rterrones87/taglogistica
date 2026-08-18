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
        'unit_category',
        'maintenance_type',
        'unit_id',
        'initial_mileage',
        'opened_at',
        'operator_id',
        'mechanic_id',
        'failure_description',
        'work_type',
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
            ->with(['unit', 'operator:id,name', 'mechanic:id,name'])
            ->withCount([
                'purchaseOrders as purchase_orders_count' => fn($query) => $query->where('status', 'Aprobada'),
            ])
            ->withSum([
                'purchaseOrders as purchase_orders_sum_cost' => fn($query) => $query->where('status', 'Aprobada'),
            ], 'cost')
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
            $data['mechanic_id'] = $data['work_type'] === 'Externo' ? null : ($data['mechanic_id'] ?? null);
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

        $data['mechanic_id'] = $data['work_type'] === 'Externo' ? null : ($data['mechanic_id'] ?? null);
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
        return DB::transaction(function () use ($userId) {
            $order = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($order->status !== 'En Proceso') {
                throw new UnprocessableEntityHttpException('La orden debe estar En Proceso para finalizarse.');
            }

            if ($order->purchaseOrders()->where('status', 'Pendiente')->exists()) {
                throw new UnprocessableEntityHttpException('No se puede finalizar la OT mientras tenga ordenes de compra pendientes.');
            }

            $order->update(['status' => 'Cerrado', 'closed_by' => $userId, 'closed_at' => now()]);

            return $order->fresh()->load(self::detailRelations());
        });
    }

    private static function detailRelations(): array
    {
        return ['unit', 'operator:id,name', 'mechanic:id,name', 'creator:id,name', 'startedBy:id,name', 'closedBy:id,name','purchaseOrders'];
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

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'work_order_id');
    }
}
