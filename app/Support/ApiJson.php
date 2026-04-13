<?php

namespace App\Support;

use Throwable;

class ApiJson
{
    /**
     * Extra JSON fields for caught exceptions (only when APP_DEBUG is true).
     *
     * @return array<string, string>
     */
    public static function debugError(Throwable $e): array
    {
        if (! config('app.debug')) {
            return [];
        }

        return ['error' => $e->getMessage()];
    }

    /**
     * Remove keys that commonly carry internal exception or trace data from API JSON.
     * Used globally when APP_DEBUG is false so legacy catch blocks cannot leak details.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function scrubSensitiveKeys(array $payload): array
    {
        foreach ([
            'error',
            'exception',
            'file',
            'line',
            'trace',
            'previous',
        ] as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }
}
