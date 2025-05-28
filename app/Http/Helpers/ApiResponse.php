<?php

namespace App\Http\Helpers;

class ApiResponse
{
    public static function success(string $message = 'Operación exitosa', $data = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code
        ], $code);
    }

    public static function error(string $message = 'Error', $errors = null, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'errors' => $errors,
            'message' => $message,
            'code' => $code
        ], $code);
    }

    public static function validation($errors, string $message = "Error de validacion",int $code = 422 )
    {
        return self::error( $message,$errors, $code);
    }

    public static function unauthorized(string $message = "No autorizado")
    {
        return self::error($message, 404);
    }
    public static function notFound(string $message = 'No encontrado')
    {
        return self::error($message, null, 404);
    }
    public static function paginated($paginator, string $message = 'Operación exitosa', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'code' => $code
        ], $code);
    }
}
