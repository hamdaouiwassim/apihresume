<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\User;

class AiUsageLogger
{
    /**
     * @param  array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int}|null  $usage
     */
    public function log(User $user, string $kind, ?int $resumeId, ?array $usage): void
    {
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
        ]);
    }
}
