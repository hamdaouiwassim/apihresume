<?php

use App\Support\ApiJson;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Do not exclude api/* — Sanctum stateful SPA requests must pass CSRF on mutating routes.
        $middleware->validateCsrfTokens(except: []);

        $middleware->statefulApi();
        $middleware->replaceInGroup(
            'api',
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\EnsureSpaRequestsAreStateful::class,
        );

        // Register custom middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'recruiter' => \App\Http\Middleware\RecruiterMiddleware::class,
            'track.activity' => \App\Http\Middleware\TrackUserActivity::class,
        ]);

        $middleware->appendToGroup('api', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\SanitizeApiJsonResponse::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\ThrottleSanctumCsrfCookie::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, \Throwable $e, Request $request) {
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
        });
    })->create();
