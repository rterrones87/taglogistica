<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\ClientPlace;
use App\Models\ClientPlaceBooth;

class ClientPlaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ClientPlace::with(['client', 'place', 'typeUnit'])
            ->where('zombie', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id'            => 'required|integer',
            'place_id'             => 'required|integer',
            'items'                => 'required|array|min:1',
            'items.*.type_unit_id' => ['required', 'integer', Rule::exists('unit_types', 'id')->where('zombie', 0)],
            'items.*.amount'       => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        // Verificar unicidad de cada combinación antes de crear
        $duplicates = [];
        foreach ($request->items as $item) {
            $exists = ClientPlace::where('client_id', $request->client_id)
                ->where('place_id', $request->place_id)
                ->where('type_unit_id', $item['type_unit_id'])
                ->where('zombie', 0)
                ->exists();

            if ($exists) {
                $duplicates[] = $item['type_unit_id'];
            }
        }

        if (!empty($duplicates)) {
            return response()->json([
                'error'      => 'Ya existen registros para los siguientes tipos de unidad en esta ruta.',
                'duplicates' => $duplicates,
            ], 400);
        }

        // Recopilar casetas existentes de hermanos ANTES de crear los nuevos registros
        $existingIds = ClientPlace::where('client_id', $request->client_id)
            ->where('place_id', $request->place_id)
            ->where('zombie', 0)
            ->pluck('id');

        $templateBooths = $existingIds->isNotEmpty()
            ? ClientPlaceBooth::whereIn('client_place_id', $existingIds)
                ->get(['booth_id', 'direction'])
                ->unique(fn($b) => $b->booth_id . '-' . $b->direction)
                ->values()
            : collect();

        $created = [];
        foreach ($request->items as $item) {
            $record = ClientPlace::create([
                'client_id'    => $request->client_id,
                'place_id'     => $request->place_id,
                'type_unit_id' => $item['type_unit_id'],
                'amount'       => $item['amount'],
            ]);

            // Copiar plantilla de casetas de los hermanos existentes
            foreach ($templateBooths as $booth) {
                ClientPlaceBooth::firstOrCreate([
                    'client_place_id' => $record->id,
                    'booth_id'        => $booth->booth_id,
                    'direction'       => $booth->direction,
                ]);
            }

            $created[] = $record->load(['client', 'place', 'typeUnit']);
        }

        return response()->json($created, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return ClientPlace::with(['client', 'place', 'typeUnit'])->find($id);
    }

    /**
     * Update the specified resource in storage.
     * Solo permite actualizar el campo amount.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        ClientPlace::find($id)->update(['amount' => $request->amount]);

        return response()->json(null, 200);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy($id)
    {
        ClientPlace::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }

    // ─── Plantilla de Casetas ─────────────────────────────────────────────────

    /**
     * Listar casetas plantilla de un client_place.
     */
    public function listBooths($id)
    {
        $clientPlace = ClientPlace::findOrFail($id);

        $siblingIds = ClientPlace::where('client_id', $clientPlace->client_id)
            ->where('place_id', $clientPlace->place_id)
            ->where('id', '!=', $id)
            ->where('zombie', 0)
            ->pluck('id');

        $booths = ClientPlaceBooth::with('booth')
            ->where('client_place_id', $id)
            ->get()
            ->map(function ($item) use ($siblingIds) {
                $item->is_unique = $siblingIds->isEmpty()
                    || !ClientPlaceBooth::whereIn('client_place_id', $siblingIds)
                        ->where('booth_id', $item->booth_id)
                        ->where('direction', $item->direction)
                        ->exists();
                return $item;
            });

        return response()->json([
            'has_siblings' => $siblingIds->isNotEmpty(),
            'outbound'     => $booths->where('direction', 'outbound')->values(),
            'return'       => $booths->where('direction', 'return')->values(),
        ]);
    }

    /**
     * Agregar una caseta a la plantilla.
     */
    public function addBooth(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'booth_id'  => 'required|integer',
            'direction' => 'required|in:outbound,return',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $exists = ClientPlaceBooth::where('client_place_id', $id)
            ->where('booth_id', $request->booth_id)
            ->where('direction', $request->direction)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Esta caseta ya está en la plantilla para esa dirección.'
            ], 400);
        }

        $record = ClientPlaceBooth::create([
            'client_place_id' => $id,
            'booth_id'        => $request->booth_id,
            'direction'       => $request->direction,
        ]);

        if ($request->boolean('replicate', false)) {
            $clientPlace = ClientPlace::find($id);
            $siblingIds  = ClientPlace::where('client_id', $clientPlace->client_id)
                ->where('place_id', $clientPlace->place_id)
                ->where('id', '!=', $id)
                ->where('zombie', 0)
                ->pluck('id');

            foreach ($siblingIds as $siblingId) {
                ClientPlaceBooth::firstOrCreate([
                    'client_place_id' => $siblingId,
                    'booth_id'        => $request->booth_id,
                    'direction'       => $request->direction,
                ]);
            }
        }

        return response()->json($record->load('booth'), 200);
    }

    /**
     * Eliminar una caseta de la plantilla.
     */
    public function removeBooth($clientPlaceId, $boothRecordId)
    {
        $record = ClientPlaceBooth::where('client_place_id', $clientPlaceId)
            ->where('id', $boothRecordId)
            ->firstOrFail();

        $boothId   = $record->booth_id;
        $direction = $record->direction;
        $record->delete();

        if (request()->boolean('replicate', false)) {
            $clientPlace = ClientPlace::find($clientPlaceId);
            $siblingIds  = ClientPlace::where('client_id', $clientPlace->client_id)
                ->where('place_id', $clientPlace->place_id)
                ->where('id', '!=', $clientPlaceId)
                ->where('zombie', 0)
                ->pluck('id');

            ClientPlaceBooth::whereIn('client_place_id', $siblingIds)
                ->where('booth_id', $boothId)
                ->where('direction', $direction)
                ->delete();
        }

        return response()->json(null, 204);
    }
}
