<?php

namespace App\Models;

use App\Support\GeneratesAnnualFolio;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FailureReport extends Model
{
    protected $fillable = [
        'folio',
        'unit_id',
        'operator_id',
        'mileage',
        'reported_at',
        'description',
        'status',
        'created_by',
        'started_by',
        'started_at',
        'finished_by',
        'finished_at'
    ];
    
    protected $casts = [
        'reported_at' => 'date:Y-m-d',
        'started_at' => 'datetime',
        'finished_at' => 'datetime'
    ];

    public static function searchList(array $filters)
    {
        $query = self::query()->with(['unit', 'operator:id,name', 'creator:id,name'])->withCount('workOrders')->latest('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public static function createRegister(array $data): self
    {
        $data['folio'] = GeneratesAnnualFolio::for(self::class, 'RF');

        return self::create($data)->load(['unit', 'operator:id,name', 'creator:id,name']);
    }

    public function detail(): self
    {
        return $this->load($this->detailRelations())->loadCount('workOrders');
    }

    public function updateRegister(array $data): self
    {
        if ($this->status === 'Finalizado') {
            throw new UnprocessableEntityHttpException('Un reporte finalizado no puede editarse.');
        }

        $this->update($data);

        return $this->fresh()->load($this->detailRelations());
    }

    public function startReport(int $userId): self
    {
        if ($this->status !== 'Abierto') {
            throw new UnprocessableEntityHttpException('Solo un reporte Abierto puede pasar a En Proceso.');
        }

        $this->update([
            'status' => 'En Proceso',
            'started_by' => $userId,
            'started_at' => now(),
        ]);

        return $this->fresh()->load($this->detailRelations());
    }

    public function finishReport(int $userId): self
    {
        if ($this->status !== 'En Proceso') {
            throw new UnprocessableEntityHttpException('Solo un reporte En Proceso puede finalizarse.');
        }

        $this->update([
            'status' => 'Finalizado',
            'finished_by' => $userId,
            'finished_at' => now(),
        ]);

        return $this->fresh()->load($this->detailRelations());
    }

    private function detailRelations(): array
    {
        return ['unit', 'operator:id,name', 'creator:id,name', 'startedBy:id,name', 'finishedBy:id,name'];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }
    public function finishedBy()
    {
        return $this->belongsTo(User::class, 'finished_by');
    }
    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'failure_report_id');
    }
}
