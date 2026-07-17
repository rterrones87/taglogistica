<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Catalog;

class CatalogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function units()
    {
        return Catalog::where('zombie', 0)
            ->where('type_collection', 'units')
            ->where('title', 'like', '%' . request('q') . '%')
            ->orderBy('id', 'desc')
            ->pluck('title')
            ->toArray();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function containers()
    {
        return Catalog::where('zombie', 0)
            ->where('type_collection', 'containers')
            ->where('title', 'like', '%' . request('q') . '%')
            ->orderBy('id', 'desc')
            ->pluck('title')
            ->toArray();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function terminals()
    {
        return Catalog::where('zombie', 0)
            ->where('type_collection', 'terminals')
            ->where('title', 'like', '%' . request('q') . '%')
            ->orderBy('id', 'desc')
            ->pluck('title')
            ->toArray();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function destines()
    {
        return Catalog::where('zombie', 0)
            ->where('type_collection', 'destines')
            ->where('title', 'like', '%' . request('q') . '%')
            ->orderBy('id', 'desc')
            ->pluck('title')
            ->toArray();
    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function container_numbers()
    {
        return Catalog::where('zombie', 0)
            ->where('type_collection', 'container-numbers')
            ->where('title', 'like', '%' . request('q') . '%')
            ->orderBy('id', 'desc')
            ->pluck('title')
            ->toArray();
    }
    
}
