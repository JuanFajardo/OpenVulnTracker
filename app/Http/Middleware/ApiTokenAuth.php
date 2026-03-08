<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'error'   => 'Token no proporcionado. Usa: Authorization: Bearer <token>',
            ], 401);
        }

        $plain = substr($header, 7);
        $token = ApiToken::findByPlain($plain);

        if (!$token) {
            return response()->json([
                'success' => false,
                'error'   => 'Token inválido.',
            ], 401);
        }

        if ($token->isExpired()) {
            return response()->json([
                'success' => false,
                'error'   => 'El token ha expirado.',
            ], 401);
        }

        $token->update(['last_used_at' => now()]);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
