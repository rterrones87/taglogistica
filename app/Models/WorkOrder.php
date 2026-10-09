<?php

namespace App\Models;

use App\Support\GeneratesAnnualFolio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
        'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'date:Y-m-d',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    private static function detailRelations(): array
    {
        return [
            'startedBy:id,name',
            'closedBy:id,name',
            'purchaseOrders:id,work_order_id,supplier_id,folio,description,cost,status',
            'purchaseOrders.supplier:id,name',
        ];
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

    public function files()
    {
        return $this->hasMany(FileWorkOrder::class)->orderBy('id');
    }

    public function detail(): self
    {
        return $this->load(self::detailRelations());
    }

    public function updateRegister(array $data, array $files = [], array $deletedFileIds = []): self
    {
        if ($this->status === 'Finalizado') {
            throw new UnprocessableEntityHttpException('Una orden cerrada no puede editarse.');
        }

        $storedFiles = [];
        $deletedFiles = [];

        $data['mechanic_id'] = $data['work_type'] === 'Externo' ? null : ($data['mechanic_id'] ?? null);

        try {
            $order = DB::transaction(function () use ($data, $files, $deletedFileIds, &$storedFiles, &$deletedFiles) {
                $order = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
                $order->load('files');

                // 1. Actualizar datos de texto de la orden
                $order->update(collect($data)->except(['deleted_file_ids', 'evidences'])->toArray());

                // 2. Procesar archivos eliminados
                $deletedFileIds = array_values(array_unique(array_map('intval', $deletedFileIds)));
                $filesToDelete = $order->files->whereIn('id', $deletedFileIds);

                if ($filesToDelete->count() !== count($deletedFileIds)) {
                    throw new UnprocessableEntityHttpException('Uno de los archivos no pertenece a la orden de trabajo.');
                }

                $remainingFiles = $order->files->whereNotIn('id', $deletedFileIds);
                $evidences = array_values($files['evidences'] ?? []);

                if ($remainingFiles->count() + count($evidences) > 5) {
                    throw new UnprocessableEntityHttpException('La orden de trabajo admite un máximo de 5 evidencias.');
                }

                foreach ($filesToDelete as $fileToDelete) {
                    $deletedFiles[] = [
                        'disk' => FileWorkOrder::DISK_FILE,
                        'name' => $fileToDelete->url,
                    ];
                    $fileToDelete->delete();
                }

                // 3. Procesar nuevas evidencias
                foreach ($evidences as $evidence) {
                    if (! $evidence instanceof UploadedFile) {
                        continue;
                    }

                    $filename = FileWorkOrder::storeFile($evidence, $storedFiles);
                    
                    $order->files()->create([
                        'url' => $filename,
                    ]);
                }

                return $order->fresh()->load(self::detailRelations());
            });
        } catch (Throwable $exception) {
            self::deleteFiles($storedFiles);
            throw $exception;
        }

        self::deleteFiles($deletedFiles);

        return $order;
    }

    public function startOrder(int $userId): self
    {
        if ($this->status !== 'Abierto') {
            throw new UnprocessableEntityHttpException('Solo una orden Abierta puede iniciar el trabajo.');
        }

        $this->update(['status' => 'En Proceso', 'started_by' => $userId, 'started_at' => now()]);

        return $this->fresh()->load(self::detailRelations());
    }

    public function changeStatus(int $status, int $userId): self
    {
        return $status === 1
            ? $this->startOrder($userId)
            : $this->closeOrder($userId);
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

            $order->update(['status' => 'Finalizado', 'closed_by' => $userId, 'closed_at' => now()]);

            return $order->fresh()->load(self::detailRelations());
        });
    }
    
    public function getEvidencesWorkOrders()
    {
        $files = $this->files()->get();

        $evidences = $files->values()->each(
            fn ($file, $index) => $file->setAttribute('evidence_number', $index + 1)
        );
        
        return $evidences;
    }

    public function addFiles(array $files): self
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($files, &$storedFiles) {
                $order = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
                $order->load('files');

                $evidences = array_values($files['evidences'] ?? []);

                if ($order->evidenceFiles->count() + count($evidences) > 5) {
                    throw new UnprocessableEntityHttpException('La orden de compra admite un maximo de 5 evidencias.');
                }

                foreach ($evidences as $evidence) {
                    if (! $evidence instanceof UploadedFile) {
                        continue;
                    }

                    $filename = FileWorkOrder::storeFile(
                        $evidence,
                        $storedFiles
                    );
                    $order->files()->create([
                        'url' => $filename,
                    ]);
                }

                return $order->fresh()->load(self::detailRelations());
            });

        } catch (Throwable $exception) {
            self::deleteFiles($storedFiles);

            throw $exception;
        }
    }

    public static function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            Storage::disk($file['disk'])->delete($file['name']);
        }
    }


    public static function searchList(array $filters)
    {
        $query = self::query()
            ->with(['unit:id,econame', 'mechanic:id,name'])
            ->withCount([
                'purchaseOrders as purchase_orders_count' => fn ($query) => $query->where('status', 'Aprobada'),
            ])
            ->withSum([
                'purchaseOrders as purchase_orders_sum_cost' => fn ($query) => $query->where('status', 'Aprobada'),
            ], 'cost')
            ->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('folio', 'like', "%{$search}%")
                    ->orWhereHas('unit', function ($unitQuery) use ($search) {
                        $unitQuery->where('econame', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['only_open'])) {
            $query->where('status', '!=', 'Finalizado');
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


}
