<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkOrderRequest;
use App\Http\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class WorkOrderController extends Controller
{
    private const LOG_CHANNEL = 'order-work';

    public function index(Request $request)
    {
        try {

            $filters = $request->only(['status', 'search']);
            $registers = WorkOrder::searchList($filters);

            return WorkOrderResource::collection($registers);

        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar el listado');
        }
    }

    public function store(WorkOrderRequest $request)
    {
        try {
            $data = array_merge($request->validated(), ['created_by' => $request->user()->id]);
            $order = WorkOrder::createRegister($data);

            Log::channel(self::LOG_CHANNEL)->info('Orden de trabajo creada.', [
                'user_id' => $request->user()->id,
                'work_order_id' => $order->id,
                'folio' => $order->folio,
            ]);

            return new WorkOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'crear la orden');
        }
    }

    public function show(Request $request, WorkOrder $workOrder)
    {
        try {
            
            $order = $workOrder->detail();

            return new WorkOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar la orden');
        }
    }

    public function update(WorkOrderRequest $request, WorkOrder $workOrder)
    {
        try {
            $order = $workOrder->updateRegister($request->validated());

            Log::channel(self::LOG_CHANNEL)->info('Orden de trabajo actualizada.', [
                'user_id' => $request->user()->id,
                'work_order_id' => $order->id,
            ]);

            return new WorkOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'actualizar la orden');
        }
    }

    public function start(Request $request, WorkOrder $workOrder)
    {
        try {
            $order = $workOrder->startOrder($request->user()->id);

            Log::channel(self::LOG_CHANNEL)->info('Orden de trabajo iniciada.', [
                'user_id' => $request->user()->id,
                'work_order_id' => $order->id,
            ]);

            return new WorkOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'iniciar la orden');
        }
    }

    public function close(Request $request, WorkOrder $workOrder)
    {
        try {
            $order = $workOrder->closeOrder($request->user()->id);

            Log::channel(self::LOG_CHANNEL)->info('Orden de trabajo cerrada.', [
                'user_id' => $request->user()->id,
                'work_order_id' => $order->id,
            ]);

            return new WorkOrderResource($order);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'cerrar la orden');
        }
    }

    private function errorResponse(Throwable $exception, string $action): JsonResponse
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        Log::channel(self::LOG_CHANNEL)->error("Error al {$action} de trabajo.", [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]);

        return response()->json([
            'message' => $status < 500 ? $exception->getMessage() : 'Ocurrio un error al procesar la orden de trabajo.',
        ], $status);
    }
}
