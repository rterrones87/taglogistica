<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreasuryPurchaseOrderRequest;
use App\Http\Resources\TreasuryPurchaseOrderResource;
use App\Models\TreasuryPurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class TreasuryPurchaseOrderController extends Controller
{
    private const LOG_CHANNEL = 'treasury-purchase-order';

    public function index(TreasuryPurchaseOrderRequest $request)
    {
        try {
            return TreasuryPurchaseOrderResource::collection(
                TreasuryPurchaseOrder::searchList($request->validated())
            );
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar el listado');
        }
    }

    public function show(TreasuryPurchaseOrder $treasuryPurchaseOrder)
    {
        try {
            return new TreasuryPurchaseOrderResource($treasuryPurchaseOrder->detail());
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar el detalle');
        }
    }

    public function markAsPaid(Request $request, TreasuryPurchaseOrder $treasuryPurchaseOrder)
    {
        try {
            $register = $treasuryPurchaseOrder->markAsPaid($request->user()->id);

            Log::channel(self::LOG_CHANNEL)->info('Orden de compra marcada como pagada.', [
                'user_id' => $request->user()->id,
                'treasury_purchase_order_id' => $register->id,
                'purchase_order_id' => $register->purchase_order_id,
            ]);

            return new TreasuryPurchaseOrderResource($register);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'marcar la orden como pagada');
        }
    }

    private function errorResponse(Throwable $exception, string $action): JsonResponse
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        Log::channel(self::LOG_CHANNEL)->error("Error al {$action} en tesoreria.", [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]);

        return response()->json([
            'message' => $status < 500 ? $exception->getMessage() : 'Ocurrio un error al procesar la orden en tesoreria.',
        ], $status);
    }
}
