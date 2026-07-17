<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;

class ClientController extends Controller
{

    public function __construct()
    {
        /*
        $this->middleware(['auth:sanctum', 'role:admin'])->only(['index', 'show']);
        $this->middleware(['auth:sanctum', 'permission:ver_pedidos'])->only(['index', 'show']);
        $this->middleware(['auth:sanctum', 'permission:crear_pedidos'])->only(['store']);
        $this->middleware(['auth:sanctum', 'permission:editar_pedidos'])->only(['update']);
        $this->middleware(['auth:sanctum', 'permission:eliminar_pedidos'])->only(['destroy']);
        */
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $query = Client::where('zombie', 0);

        if (request()->has('q') && !empty(request('q'))) {
            $q = request('q');
            $query->where('name', 'like', '%' . $q . '%'); 
        }

        return $query->orderBy('id', 'desc')->get();
    }

   
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'company_type' => 'required',
            'RFC' => 'required',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $client = Client::create($data);

        return response()->json($client, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Client::find($id); 
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'company_type' => 'required',
            'RFC' => 'required',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $client = Client::find($id);

        $client->update($data);


       /* $client->destinations()->delete();

        foreach ($data['formatted_destinations'] as $destination) {
            $placeId = $destination['place_id'];
            foreach ($destination['booths'] as $booth) {
                $client->destinations()->create([
                    'place_id' => $placeId,
                    'booth_id' => $booth['booth_id']
                ]);
            }
        }*/

        return response()->json(null, 200);
    }

    /**
     * Logic remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Client::find($id)->update(['zombie' => 1]);

        return response()->json(null, 204);
    }
}
