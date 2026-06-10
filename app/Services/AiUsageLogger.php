<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\User;

class AiUsageLogger
{
    private const MAX_JSON_BYTES = 120_000;

    /**
     * @param  array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int}|null  $usage
     * @param  array<string, mixed>|null  $requestPayload
     * @param  array<string, mixed>|null  $responsePayload
     */
    public function log(
        User $user,
        string $kind,
        ?int $resumeId,
        ?array $usage,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
    ): void {
        $raw = strtolower((string) config('services.ai.provider', 'openai'));
        $provider = $raw === 'groq' ? 'groq' : 'openai';

        $model = $provider === 'groq'
            ? (string) config('services.groq.model', '')
            : (string) config('services.openai.model', '');

        $prompt = (int) data_get($usage, 'prompt_tokens', 0);
        $completion = (int) data_get($usage, 'completion_tokens', 0);
        $total = (int) data_get($usage, 'total_tokens', 0);
        if ($total <= 0 && ($prompt > 0 || $completion > 0)) {
            $total = $prompt + $completion;
        }

        AiUsageLog::create([
            'user_id' => $user->id,
            'kind' => $kind,
            'resume_id' => $resumeId,
            'provider' => $provider,
            'model' => $model !== '' ? $model : null,
            'prompt_tokens' => max(0, $prompt),
            'completion_tokens' => max(0, $completion),
            'total_tokens' => max(0, $total),
            'request_payload' => $this->normalizePayload($requestPayload),
            'response_payload' => $this->normalizePayload($responsePayload),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function normalizePayload(?array $payload): ?array
    {
        if ($payload === null || $payload === []) {
            return null;
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return ['_error' => 'Could not encode payload'];
        }

        if (strlen($encoded) <= self::MAX_JSON_BYTES) {
            return $payload;
        }

        return [
            '_truncated' => true,
            '_original_bytes' => strlen($encoded),
            'preview' => mb_substr($encoded, 0, self::MAX_JSON_BYTES - 200).'…',
        ];
    }
}
