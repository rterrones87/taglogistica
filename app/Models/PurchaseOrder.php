<?php

namespace App\Models;

use App\Support\GeneratesAnnualFolio;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

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
        'status',
        'created_by',
        'treasury_accepted_by',
        'treasury_accepted_at',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'treasury_accepted_at' => 'datetime',
    ];

    public static function searchList(array $filters)
    {
        $query = self::query()->with(self::detailRelations())->latest('id');

        if (! empty($filters['work_order_id'])) {
            $query->where('work_order_id', $filters['work_order_id']);
        }

        if (! empty($filters['status'])) {
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
            $order = self::create($data);
            $order->requestApproval('purchase_order', $data['created_by'], [
                'Folio OC' => $order->folio,
                'Folio OT' => $workOrder->folio,
                'Costo con IVA' => number_format((float) $order->cost, 2, '.', ','),
                'Descripcion' => $order->description,
            ]);

            return $order->addFiles($files);
        });
    }

    public function detail(): self
    {
        return $this->load(self::detailRelations());
    }

    public function updateRegister(array $data, array $files = [], array $deletedFileIds = []): self
    {
        $storedFiles = [];
        $deletedFiles = [];

        try {
            $order = DB::transaction(function () use ($data, $files, $deletedFileIds, &$storedFiles, &$deletedFiles) {
                $order = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
                $order->load('files');

                $deletedFileIds = array_values(array_unique(array_map('intval', $deletedFileIds)));
                $filesToDelete = $order->files->whereIn('id', $deletedFileIds);

                if ($filesToDelete->count() !== count($deletedFileIds)) {
                    throw new UnprocessableEntityHttpException('Uno de los archivos no pertenece a la orden de compra.');
                }

                $remainingFiles = $order->files->whereNotIn('id', $deletedFileIds);
                $quotation = $files['quotation'] ?? null;
                $evidences = array_values($files['evidences'] ?? []);

                if ($quotation instanceof UploadedFile && $remainingFiles->where('type', FilePurchaseOrder::TYPE_QUOTATION)->isNotEmpty()) {
                    throw new UnprocessableEntityHttpException('La orden de compra ya tiene una cotizacion.');
                }

                if ($remainingFiles->where('type', FilePurchaseOrder::TYPE_EVIDENCE)->count() + count($evidences) > 5) {
                    throw new UnprocessableEntityHttpException('La orden de compra admite un maximo de 5 evidencias.');
                }

                $order->update([
                    'payment_condition' => $data['payment_condition'] ?? null,
                    'credit_days' => ($data['payment_condition'] ?? null) === 'Credito'
                        ? $data['credit_days']
                        : null,
                ]);

                foreach ($filesToDelete as $fileToDelete) {
                    $deletedFiles[] = [
                        'disk' => FilePurchaseOrder::diskForType($fileToDelete->type),
                        'name' => $fileToDelete->url,
                    ];
                    $fileToDelete->delete();
                }

                if ($quotation instanceof UploadedFile) {
                    $filename = self::storeFile(
                        $quotation,
                        FilePurchaseOrder::TYPE_QUOTATION,
                        $storedFiles
                    );
                    $order->files()->create([
                        'type' => FilePurchaseOrder::TYPE_QUOTATION,
                        'url' => $filename,
                    ]);
                }

                foreach ($evidences as $evidence) {
                    if (! $evidence instanceof UploadedFile) {
                        continue;
                    }

                    $filename = self::storeFile(
                        $evidence,
                        FilePurchaseOrder::TYPE_EVIDENCE,
                        $storedFiles
                    );
                    $order->files()->create([
                        'type' => FilePurchaseOrder::TYPE_EVIDENCE,
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

        if (! $this->treasury_accepted_at) {
            $this->update([
                'treasury_accepted_by' => $userId,
                'treasury_accepted_at' => now(),
            ]);
        }

        return $this->fresh()->load(self::detailRelations());
    }

    public function addFiles(array $files): self
    {
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($files, &$storedFiles) {
                $order = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
                $order->load('files');

                $quotation = $files['quotation'] ?? null;
                $evidences = array_values($files['evidences'] ?? []);

                if ($quotation instanceof UploadedFile && $order->quotationFile) {
                    throw new UnprocessableEntityHttpException('La orden de compra ya tiene una cotizacion.');
                }

                if ($order->evidenceFiles->count() + count($evidences) > 5) {
                    throw new UnprocessableEntityHttpException('La orden de compra admite un maximo de 5 evidencias.');
                }

                if ($quotation instanceof UploadedFile) {
                    $filename = self::storeFile(
                        $quotation,
                        FilePurchaseOrder::TYPE_QUOTATION,
                        $storedFiles
                    );
                    $order->files()->create([
                        'type' => FilePurchaseOrder::TYPE_QUOTATION,
                        'url' => $filename,
                    ]);
                }

                foreach ($evidences as $evidence) {
                    if (! $evidence instanceof UploadedFile) {
                        continue;
                    }

                    $filename = self::storeFile(
                        $evidence,
                        FilePurchaseOrder::TYPE_EVIDENCE,
                        $storedFiles
                    );
                    $order->files()->create([
                        'type' => FilePurchaseOrder::TYPE_EVIDENCE,
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

    private static function storeFile(UploadedFile $file, int $type, array &$storedFiles): string
    {
        $disk = FilePurchaseOrder::diskForType($type);
        $filename = $file->store('', $disk);

        if (! is_string($filename)) {
            throw new UnprocessableEntityHttpException('No fue posible guardar el archivo de la orden de compra.');
        }

        $storedFiles[] = [
            'disk' => $disk,
            'name' => $filename,
        ];

        return $filename;
    }

    private static function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            Storage::disk($file['disk'])->delete($file['name']);
        }
    }

    public function files()
    {
        return $this->hasMany(FilePurchaseOrder::class)->orderBy('type')->orderBy('id');
    }

    public function quotationFile()
    {
        return $this->hasOne(FilePurchaseOrder::class)
            ->where('type', FilePurchaseOrder::TYPE_QUOTATION);
    }

    public function evidenceFiles()
    {
        return $this->hasMany(FilePurchaseOrder::class)
            ->where('type', FilePurchaseOrder::TYPE_EVIDENCE);
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
            'files',
        ];
    }
}
