<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lets Blade page controllers reuse the existing JSON API controller actions, so pages get
 * exactly the data the React SPA used to fetch (same queries, permissions and shape)
 * without duplicating business logic.
 *
 *   $payload = ApiBridge::call([ResumeController::class, 'index']);
 *   $payload = ApiBridge::call([BlogController::class, 'show'], ['slug' => $slug], ['per_page' => 12]);
 */
class ApiBridge
{
    /**
     * @param  array{0: class-string, 1: string}  $action
     * @param  array<string, mixed>  $routeParams  named controller arguments (route parameters)
     * @param  array<string, mixed>  $query  request input for the sub-request
     * @return array{status: int, data: array}
     */
    public static function call(array $action, array $routeParams = [], array $query = []): array
    {
        [$class, $method] = $action;
        $current = request();

        $sub = Request::create('/api/_bridge', 'GET', $query);
        $sub->setUserResolver($current->getUserResolver());
        if ($current->hasSession()) {
            $sub->setLaravelSession($current->session());
        }
        $sub->headers->set('Accept', 'application/json');
        $sub->server->set('REMOTE_ADDR', $current->ip());

        $previous = app('request');
        app()->instance('request', $sub);

        try {
            $response = app()->call([app($class), $method], array_merge(['request' => $sub], $routeParams));
        } finally {
            app()->instance('request', $previous);
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            return [
                'status' => $response->getStatusCode(),
                'data' => is_array($data) && ! config('app.debug') ? ApiJson::scrubSensitiveKeys($data) : (array) $data,
            ];
        }

        return ['status' => 200, 'data' => is_array($response) ? $response : []];
    }

    /** Shortcut: return only the decoded payload. */
    public static function data(array $action, array $routeParams = [], array $query = []): array
    {
        return self::call($action, $routeParams, $query)['data'];
    }
}
