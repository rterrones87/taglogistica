<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WorkshopCatalogController extends Controller
{
    private const LOG_CHANNEL = 'maintenance-catalog';

    public function index(Request $request)
    {

        try {
            $catalogs = [
                'units' => Unit::searchList(['columns' => ['id', 'econame', 'type']]),
                'operators' => User::searchList(['role_id' => 8, 'columns' => ['id', 'name']]),
                'mechanics' => User::searchList(['role_id' => 8, 'columns' => ['id', 'name']]),
                'suppliers' => Supplier::searchList(['columns' => ['id', 'name']]),
                'work_orders' => WorkOrder::searchList(['only_open' => true]),
            ];

            return response()->json($catalogs);

        } catch (Throwable $exception) {

            Log::channel(self::LOG_CHANNEL)->error('Error al consultar catalogos de mantenimiento.', [
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'message' => 'Ocurrio un error al consultar los catalogos de mantenimiento.',
            ], 500);
        }
    }
}
