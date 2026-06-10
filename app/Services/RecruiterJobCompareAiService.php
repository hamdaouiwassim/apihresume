<?php

namespace App\Services;

use App\Models\RecruiterJob;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RecruiterJobCompareAiService
{
    /**
     * @param  array<int, array<string, mixed>>  $phaseOneResults  From RecruiterJobMatchService (max 4)
     * @param  array<int, array<string, mixed>>  $resumeSummaries
     * @return array{insights: array<int, array<string, mixed>>, usage: array<string, int>|null}
     */
    public function deepInsights(RecruiterJob $job, array $phaseOneResults, array $resumeSummaries): array
    {
        $completion = $this->requestChatCompletion([
            [
                'role' => 'system',
                'content' => 'You are an expert recruiter. Return strict JSON only. Do not change fit scores; only add narrative insights.',
            ],
            [
                'role' => 'user',
                'content' => $this->buildPrompt($job, $phaseOneResults, $resumeSummaries),
            ],
        ]);

        $decoded = json_decode($this->extractJson($completion['content']), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('AI returned an invalid response format.');
        }

        return [
            'insights' => $this->normalizeInsights($decoded),
            'usage' => $completion['usage'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $phaseOneResults
     * @param  array<int, array<string, mixed>>  $resumeSummaries
     */
    private function buildPrompt(RecruiterJob $job, array $phaseOneResults, array $resumeSummaries): string
    {
        $jobPayload = [
            'title' => $job->title,
            'company' => $job->company_name,
            'description' => $job->description,
            'required_skills' => $job->required_skills ?? [],
            'experience' => $job->experience_level_label,
            'education' => $job->education_requirements ?? [],
        ];

        return implode("\n", [
            'Compare up to 4 candidates for this job. Fit scores are already computed — do NOT change them.',
            'Return JSON:',
            '{',
            '  "candidates": [',
            '    {',
            '      "resume_id": 1,',
            '      "summary": "2-3 sentences on fit",',
            '      "strengths": ["..."],',
            '      "risks": ["..."],',
            '      "interview_questions": ["..."]',
            '    }',
            '  ]',
            '}',
            '',
            'JOB:',
            json_encode($jobPayload, JSON_UNESCAPED_UNICODE),
            '',
            'RULE_SCORES (reference only):',
            json_encode($phaseOneResults, JSON_UNESCAPED_UNICODE),
            '',
            'RESUME_SUMMARIES:',
            json_encode($resumeSummaries, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeInsights(array $decoded): array
    {
        $out = [];
        foreach (($decoded['candidates'] ?? []) as $item) {
            if (! is_array($item) || ! isset($item['resume_id'])) {
                continue;
            }
            $out[] = [
                'resume_id' => (int) $item['resume_id'],
                'summary' => (string) ($item['summary'] ?? ''),
                'strengths' => array_values(array_filter(array_map('strval', (array) ($item['strengths'] ?? [])))),
                'risks' => array_values(array_filter(array_map('strval', (array) ($item['risks'] ?? [])))),
                'interview_questions' => array_values(array_filter(array_map('strval', (array) ($item['interview_questions'] ?? [])))),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int}|null}
     */
    private function requestChatCompletion(array $messages): array
    {
        [$baseUrl, $apiKey, $model] = $this->resolveProviderConfig();

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post(rtrim($baseUrl, '/').'/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.4,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('AI provider error: '.$response->body());
        }

        $body = $response->json();
        $content = (string) data_get($body, 'choices.0.message.content', '');
        $usage = data_get($body, 'usage');

        return [
            'content' => $content,
            'usage' => is_array($usage) ? [
                'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            ] : null,
        ];
    }

    private function extractJson(string $content): string
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return '{}';
        }
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```[a-zA-Z]*\s*/', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/```$/', '', $trimmed) ?? $trimmed;
            $trimmed = trim($trimmed);
        }

        return $trimmed;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveProviderConfig(): array
    {
        $provider = strtolower((string) config('services.ai.provider', 'openai'));

        if ($provider === 'groq') {
            return [
                (string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'),
                (string) config('services.groq.api_key', ''),
                (string) config('services.groq.model', 'llama-3.3-70b-versatile'),
            ];
        }

        return [
            (string) config('services.openai.base_url', 'https://api.openai.com/v1'),
            (string) config('services.openai.api_key', ''),
            (string) config('services.openai.model', 'gpt-4o-mini'),
        ];
    }
}
