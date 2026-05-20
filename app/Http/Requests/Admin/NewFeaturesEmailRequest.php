<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NewFeaturesEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:200'],
            'headline' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:8000'],
            'links' => ['required', 'array', 'min:1', 'max:10'],
            'links.*.label' => ['required', 'string', 'max:120'],
            'links.*.url' => ['required', 'url', 'max:500'],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function normalizedLinks(): array
    {
        return collect($this->validated('links'))
            ->map(fn (array $link) => [
                'label' => trim($link['label']),
                'url' => trim($link['url']),
            ])
            ->values()
            ->all();
    }

    public function headline(): string
    {
        return trim((string) ($this->validated('headline') ?: $this->validated('subject')));
    }
}
