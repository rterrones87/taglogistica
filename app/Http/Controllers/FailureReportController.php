<?php

namespace App\Http\Controllers;

use App\Http\Requests\FailureReportRequest;
use App\Http\Resources\FailureReportResource;
use App\Models\FailureReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class FailureReportController extends Controller
{
    private const LOG_CHANNEL = 'failure-report';

    public function index(Request $request)
    {
        try {
            
            $filters = $request->only(['status']);
            $registers = FailureReport::searchList($filters);

            return FailureReportResource::collection($registers);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar el listado');
        }
    }

    public function store(FailureReportRequest $request)
    {
        try {
            $data = array_merge($request->validated(), ['created_by' => $request->user()->id]);
            $report = FailureReport::createRegister($data);

            Log::channel(self::LOG_CHANNEL)->info('Reporte de falla creado.', [
                'user_id' => $request->user()->id,
                'failure_report_id' => $report->id,
                'folio' => $report->folio,
            ]);

            return new FailureReportResource($report);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'crear el reporte');
        }
    }

    public function show(Request $request, FailureReport $failureReport)
    {
        try {
            
            $report = $failureReport->detail();

            return new FailureReportResource($report);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'consultar el reporte');
        }
    }

    public function update(FailureReportRequest $request, FailureReport $failureReport)
    {
        try {
            $report = $failureReport->updateRegister($request->validated());

            Log::channel(self::LOG_CHANNEL)->info('Reporte de falla actualizado.', [
                'user_id' => $request->user()->id,
                'failure_report_id' => $report->id,
            ]);

            return new FailureReportResource($report);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'actualizar el reporte');
        }
    }

    public function start(Request $request, FailureReport $failureReport)
    {
        try {
            $report = $failureReport->startReport($request->user()->id);

            Log::channel(self::LOG_CHANNEL)->info('Reporte de falla cambiado a En Proceso.', [
                'user_id' => $request->user()->id,
                'failure_report_id' => $report->id,
            ]);

            return new FailureReportResource($report);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'iniciar el reporte');
        }
    }

    public function finish(Request $request, FailureReport $failureReport)
    {
        try {
            $report = $failureReport->finishReport($request->user()->id);

            Log::channel(self::LOG_CHANNEL)->info('Reporte de falla finalizado.', [
                'user_id' => $request->user()->id,
                'failure_report_id' => $report->id,
            ]);

            return new FailureReportResource($report);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception, 'finalizar el reporte');
        }
    }

    private function errorResponse(Throwable $exception, string $action): JsonResponse
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        Log::channel(self::LOG_CHANNEL)->error("Error al {$action} de falla.", [
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]);

        return response()->json([
            'message' => $status < 500 ? $exception->getMessage() : 'Ocurrio un error al procesar el reporte de falla.',
        ], $status);
    }
}
