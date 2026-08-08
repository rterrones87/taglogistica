<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseOrderRequest;
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

    public function show(Request $request, PurchaseOrder $maintenancePurchaseOrder)
    {
        try {

            $order = $maintenancePurchaseOrder->detail();

            return new PurchaseOrderResource($order);

        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar la orden');
        }
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $maintenancePurchaseOrder)
    {
        try {

            $order = $maintenancePurchaseOrder->updateRegister($request->validated(), $request->allFiles());

            Log::channel(self::LOG_CHANNEL)->info('Orden de compra actualizada.', [
                'user_id' => $request->user()->id,
                'purchase_order_id' => $order->id,
            ]);

            return new PurchaseOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'actualizar la orden');
        }
    }

    public function close(Request $request, PurchaseOrder $maintenancePurchaseOrder)
    {
        try {
            
            $order = $maintenancePurchaseOrder->closeOrder($request->user()->id);

            Log::channel(self::LOG_CHANNEL)->info('Orden de compra cerrada.', [
                'user_id' => $request->user()->id,
                'purchase_order_id' => $order->id,
            ]);

            return new PurchaseOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'cerrar la orden');
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
