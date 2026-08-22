<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Http\Requests\PurchaseOrderRequest;
use App\Http\Requests\PurchaseOrderUpdateRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PurchaseOrderController extends Controller
{
    private const LOG_CHANNEL = 'purchase-order';

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['work_order_id', 'status']);
            $registers = PurchaseOrder::searchList($filters);

            return PurchaseOrderResource::collection($registers);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar el listado');
        }
    }

    public function store(PurchaseOrderRequest $request)
    {
        try {
            $data = array_merge($request->validated(), ['created_by' => $request->user()->id]);
            $order = PurchaseOrder::createRegister($data, $request->allFiles());

            if (env('APP_ENV') != 'local') {
                NotificationHelper::notifyAdministrators(
                    'Nueva orden de compra pendiente',
                    "Se requiere aprobar o rechazar la orden {$order->folio}.",
                    ['purchase_order_id' => (string) $order->id, 'folio' => $order->folio]
                );
            }

            Log::channel(self::LOG_CHANNEL)->info('Orden de compra creada.', [
                'user_id' => $request->user()->id,
                'purchase_order_id' => $order->id,
                'folio' => $order->folio,
            ]);

            return new PurchaseOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'crear la orden');
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        try {
            $order = $purchaseOrder->detail();

            return new PurchaseOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar la orden');
        }
    }

    public function update(PurchaseOrderUpdateRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $order = $purchaseOrder->updateRegister(
                $request->validated(),
                $request->allFiles(),
                $request->input('deleted_file_ids', [])
            );

            Log::channel(self::LOG_CHANNEL)->info('Orden de compra actualizada.', [
                'user_id' => $request->user()->id,
                'purchase_order_id' => $order->id,
            ]);

            return new PurchaseOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'actualizar la orden');
        }
    }

    public function treasuryIndex(Request $request)
    {
        try {
            $filters = [
                'accepted' => $request->filled('accepted') ? $request->boolean('accepted') : null,
            ];
            $registers = PurchaseOrder::searchTreasuryList($filters);

            return PurchaseOrderResource::collection($registers);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar las ordenes aprobadas en tesoreria');
        }
    }

    public function treasuryShow(PurchaseOrder $purchaseOrder)
    {
        try {
            return new PurchaseOrderResource($purchaseOrder->detail());
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar el detalle en tesoreria');
        }
    }

    public function acceptForTreasury(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $order = $purchaseOrder->acceptForTreasury($request->user()->id);

            Log::channel(self::LOG_CHANNEL)->info('Orden de compra aceptada por tesoreria.', [
                'user_id' => $request->user()->id,
                'purchase_order_id' => $order->id,
            ]);

            return new PurchaseOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'aceptar la orden en tesoreria');
        }
    }

    private function errorResponse(Throwable $exception, string $action): JsonResponse
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        Log::channel(self::LOG_CHANNEL)->error("Error al {$action} de compra.", [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]);

        return response()->json([
            'message' => $status < 500 ? $exception->getMessage() : 'Ocurrio un error al procesar la orden de compra.',
        ], $status);
    }
}
