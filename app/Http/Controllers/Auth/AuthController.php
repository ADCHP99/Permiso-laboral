<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request){
        $request->validate(
            ['nombre_usuario' => 'required|string',
            'password' => 'required|string'],
            [
                'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
                'nombre_usuario.string'   => 'El nombre de usuario debe ser un texto.',
                'password.required'       => 'La contraseña es obligatoria.',
                'password.string'         => 'La contraseña debe ser un texto.'
            ]);
        $usuario = Usuario::where('nombre_usuario',$request->nombre_usuario)->first();
        if(!$usuario || !Hash::check($request->password, $usuario->password)){
            return ApiResponse::error('Credenciales incorrectas',null,401);
        }
        $token = $usuario->createToken('api-token')->plainTextToken;
        $data=[
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'nombre_usuario' => $usuario->nombre_usuario,
                'empleado' => [
                    'id' => $usuario->empleado?->id,
                    'nombre' => $usuario->empleado?->nombre,
                    'apellido' => $usuario->empleado?->apellido,
                    'correo'=> $usuario->empleado?->correo,
                    'cedula' => $usuario->empleado?->cedula,
                    'telefono' => $usuario->empleado?->telefono,
                    'cargo' => $usuario->empleado?->cargo,
                    'rol' => $usuario->empleado?->rol,
                    'departamento' => $usuario->empleado?->departamento,
                ]
            ]
        ];
        return ApiResponse::success("Inicio de Sesion exitoso",$data);
    }
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return ApiResponse::success("Sesion Cerrada");
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('empleado');

        $data = [
            'id' => $user->id,
            'nombre_usuario' => $user->nombre_usuario,
            'empleado' => [
                'id' => $user->empleado?->id,
                'nombre' => $user->empleado?->nombre,
                'apellido' => $user->empleado?->apellido,
                'cedula' => $user->empleado?->cedula,
                'telefono' => $user->empleado?->telefono,
                'cargo' => $user->empleado?->cargo,
                'rol' => $user->empleado?->rol,
            ]
            ];
        return ApiResponse::success("Usuario autenticado",$data);
    }

}
