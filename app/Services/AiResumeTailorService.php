<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiResumeTailorService
{
    public function tailorResume(array $payload): array
    {
        [$baseUrl, $apiKey, $model] = $this->resolveProviderConfig();
        $content = $this->requestChatCompletion(
            $baseUrl,
            $apiKey,
            $model,
            [
                [
                    'role' => 'system',
                    'content' => 'You are an expert resume coach. Return strict JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($payload),
                ],
            ]
        );
        $decoded = json_decode($this->extractJson($content), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI returned an invalid response format.');
        }

        return $this->normalize($decoded);
    }

    public function enhanceText(string $text, ?string $context = null): string
    {
        [$baseUrl, $apiKey, $model] = $this->resolveProviderConfig();

        $prompt = implode("\n", [
            'Enhance the following text for professional quality and clarity.',
            'Keep original meaning and intent.',
            'Do not invent facts, achievements, or metrics.',
            'Return plain text only.',
            $context ? "Context: {$context}" : 'Context: professional writing',
            '',
            'Text:',
            $text,
        ]);

        $content = $this->requestChatCompletion(
            $baseUrl,
            $apiKey,
            $model,
            [
                [
                    'role' => 'system',
                    'content' => 'You improve user writing while preserving intent.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ]
        );

        $enhanced = trim($content);
        if ($enhanced === '') {
            throw new RuntimeException('AI returned empty enhanced text.');
        }

        return $enhanced;
    }

    protected function buildPrompt(array $payload): string
    {
        $resume = $payload['resume'] ?? [];
        $targetRole = $payload['target_role'] ?? null;
        $seniority = $payload['seniority'] ?? null;
        $jobDescription = $payload['job_description'] ?? '';

        $instructions = [
            'Return ONLY a valid JSON object.',
            'Keep suggestions concise and practical.',
            'Do not invent fake metrics unless clearly marked as estimated.',
            'Prefer rewriting to stronger action-oriented language.',
            'Output shape:',
            '{',
            '  "summary_suggestion": { "text": "..." },',
            '  "experience_suggestions": [',
            '    { "experience_id": 1, "improved_description": "...", "reason": "..." }',
            '  ],',
            '  "skills_to_add": ["..."],',
            '  "ats_keywords": ["..."]',
            '}',
        ];

        return implode("\n", $instructions)
            ."\n\nTARGET ROLE: ".($targetRole ?: 'N/A')
            ."\nSENIORITY: ".($seniority ?: 'N/A')
            ."\nJOB DESCRIPTION:\n".$jobDescription
            ."\n\nRESUME JSON:\n".json_encode($resume, JSON_UNESCAPED_UNICODE);
    }

    protected function extractJson(string $content): string
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

    protected function normalize(array $decoded): array
    {
        $summary = is_array($decoded['summary_suggestion'] ?? null)
            ? (string) ($decoded['summary_suggestion']['text'] ?? '')
            : '';

        $experienceSuggestions = [];
        foreach (($decoded['experience_suggestions'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $experienceSuggestions[] = [
                'experience_id' => $item['experience_id'] ?? null,
                'improved_description' => (string) ($item['improved_description'] ?? ''),
                'reason' => (string) ($item['reason'] ?? ''),
            ];
        }

        $skillsToAdd = array_values(array_filter(
            array_map('strval', (array) ($decoded['skills_to_add'] ?? []))
        ));
        $atsKeywords = array_values(array_filter(
            array_map('strval', (array) ($decoded['ats_keywords'] ?? []))
        ));

        return [
            'summary_suggestion' => ['text' => $summary],
            'experience_suggestions' => $experienceSuggestions,
            'skills_to_add' => $skillsToAdd,
            'ats_keywords' => $atsKeywords,
        ];
    }

    protected function resolveProviderConfig(): array
    {
        $provider = strtolower((string) config('services.ai.provider', 'openai'));

        if ($provider === 'groq') {
            $apiKey = (string) config('services.groq.api_key', '');
            if ($apiKey === '') {
                throw new RuntimeException('Groq AI provider is not configured.');
            }

            $baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
            $model = (string) config('services.groq.model', 'llama-3.1-8b-instant');

            return [$baseUrl, $apiKey, $model];
        }

        $apiKey = (string) config('services.openai.api_key', '');
        if ($apiKey === '') {
            throw new RuntimeException('OpenAI provider is not configured.');
        }

        $baseUrl = 'https://api.openai.com/v1';
        $model = (string) config('services.openai.model', 'gpt-4o-mini');

        return [$baseUrl, $apiKey, $model];
    }

    protected function requestChatCompletion(string $baseUrl, string $apiKey, string $model, array $messages): string
    {
        $response = Http::timeout(30)
            ->withToken($apiKey)
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'temperature' => 0.4,
                'messages' => $messages,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('AI request failed.');
        }

        return (string) data_get($response->json(), 'choices.0.message.content', '');
    }
}
