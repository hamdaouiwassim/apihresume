<?php

namespace App\Http\Middleware;

use App\Support\ApiJson;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeApiJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('app.debug') || ! $request->is('api/*')) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data)) {
                $response->setData(ApiJson::scrubSensitiveKeys($data));
            }
        }

        return $response;
    }
}
