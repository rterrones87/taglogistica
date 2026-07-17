<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IdempotencyMiddleware
{
    /**
     * TTL en segundos (1 hora)
     */
    private const TTL_SECONDS = 3600;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        // Si no hay header, procesar normalmente
        if (empty($idempotencyKey)) {
            return $next($request);
        }

        // Generar cache key único por usuario y endpoint
        $userId = auth()->id() ?? 'guest';
        $endpoint = $request->path();
        $cacheKey = "idempotency:{$userId}:{$endpoint}:{$idempotencyKey}";

        // Verificar si ya existe una respuesta cacheada
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return response()->json(
                $cached['body'],
                $cached['status']
            )->withHeaders(['X-Idempotent-Replayed' => 'true']);
        }

        // Procesar el request
        $response = $next($request);

        // Solo cachear respuestas exitosas (2xx)
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'body' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode(),
            ], self::TTL_SECONDS);
        }

        return $response;
    }
}
