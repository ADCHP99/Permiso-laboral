<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request){
        $request->validate([
           'nombre_usuario' => 'required | string',
            'password' => 'required | string'
        ]);
        $usuario = Usuario::where('nombre_usuario',$request->nombre_usuario)->first();
        if(!$usuario || !Hash::check($request->password, $usuario->password)){
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ]);
        }
        $token = $usuario->createToken('api-oken')->plainTextToken;
        return response()->json([
            'token' => $token,
            'usuario' => $usuario
        ]);
    }
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesion cerrada']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('empleado'); // Cargamos la relación empleado

        return response()->json([
            'id' => $user->id,
            'nombre_usuario' => $user->nombre_usuario,
            'empleado' => $user->empleado ? [
                'id' => $user->empleado->id,
                'nombre' => $user->empleado->nombre,
                'apellido' => $user->empleado->apellido,
                'cedula' => $user->empleado->cedula,
                'telefono' => $user->empleado->telefono,
                'cargo' => $user->empleado->cargo,
                'rol' => $user->empleado->rol,
            ] : null,
        ]);
    }

}
