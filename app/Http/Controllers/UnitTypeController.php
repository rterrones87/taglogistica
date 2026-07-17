<?php

namespace App\Http\Controllers;

use App\Models\UnitType;

class UnitTypeController extends Controller
{
    /**
     * Devuelve el listado de tipos de unidad activos.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return UnitType::query()
            ->where('zombie', 0)
            ->orderBy('id')
            ->get(['id', 'name']);
    }
}
