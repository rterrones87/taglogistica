<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Role;

use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id
        ]);

        return response()->json(['token' => $user->createToken('API Token')->plainTextToken]);
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }

        $user = User::with('role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        // Obtener permisos del rol del usuario
        $permissions = $user->role->permissions()->pluck('name')->toArray();

        return response()->json([
            'token' => $user->createToken('API Token')->plainTextToken, 
            'user' => $user,
            'permissions' => $permissions
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Cierre de sesión exitoso']);
    }

    public function roles()
    {
        return Role::where('id', '>', 1)->orderBy('id','desc')->get(); 
    }

    public function password(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])([A-Za-z\d$@$!%*?&]|[^ ]){8,15}$/',
                'confirmed'
            ]
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }
        
        $data = $request->all();

        $password = Hash::make($data['password']);

        User::find($id)->update(['password' => $password]);

        return response()->json(null, 200);
        //$2y$10$eI.lhVRO4JcIsOtXD4bxpeWT4bgt/Pd8sC1oZG05sgenhSrkzOYe.
    }

    public function fcm_token_register(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'fcm_token' => 'required'
        ]);

        
        if ($validator->fails()) {

            return response()->json($validator->messages(), 400);

        }

        $data = $request->all();

        $user = User::find($data['user_id']);

        $user->update(['fcm_token' => $data['fcm_token']]);

        return response()->json(null, 200);

    }
}
