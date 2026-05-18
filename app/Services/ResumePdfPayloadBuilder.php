<?php

namespace App\Services;

use App\Models\Resume;
use Carbon\Carbon;

/**
 * Builds the normalized resume array expected by pdf.resume (same shape as frontend buildResumeTemplateData).
 */
class ResumePdfPayloadBuilder
{
    public function fromResume(Resume $resume, string $locale = 'en'): array
    {
        $resume->loadMissing([
            'basicInfo',
            'experiences.projects',
            'educations',
            'skills',
            'hobbies',
            'certificates',
            'languages',
            'projects',
            'template',
        ]);

        $basic = $resume->basicInfo;
        $localeTag = $locale === 'fr' ? 'fr-FR' : 'en-US';
        $presentLabel = $locale === 'fr' ? 'En cours' : 'Present';

        $templateLayout = $this->deriveTemplateLayout($resume->template);
        $showPhoto = (bool) ($basic?->show_photo_on_cv ?? true);

        $contact = [
            'location' => (string) ($basic?->location ?? ''),
            'email' => (string) ($basic?->email ?? ''),
            'phone' => (string) ($basic?->phone ?? ''),
            'linkedin' => (string) ($basic?->linkedin ?? ''),
            'github' => (string) ($basic?->github ?? ''),
            'website' => (string) ($basic?->website ?? ''),
            'profile_picture' => $showPhoto ? (string) ($basic?->avatar ?? '') : '',
        ];

        $rawProjects = $resume->projects ?? collect();
        $experiences = $resume->experiences ?? collect();

        $experience = $experiences->map(function ($exp) use ($rawProjects, $localeTag, $presentLabel) {
            $start = $this->formatDate($exp->startDate ?? $exp->start_date ?? null, $localeTag);
            $isPresent = (bool) ($exp->is_present ?? false);
            $endRaw = $exp->endDate ?? $exp->end_date ?? null;
            $end = $isPresent
                ? $presentLabel
                : ($endRaw ? $this->formatDate($endRaw, $localeTag) : ($start ? $presentLabel : ''));

            $expId = $exp->id;
            $roleProjects = collect();
            if ($exp->relationLoaded('projects') && $exp->projects->isNotEmpty()) {
                $roleProjects = $exp->projects->map(fn ($p) => $this->formatProject($p, $localeTag, $presentLabel));
            } else {
                $roleProjects = $rawProjects
                    ->filter(fn ($p) => (int) ($p->experience_id ?? 0) === (int) $expId)
                    ->map(fn ($p) => $this->formatProject($p, $localeTag, $presentLabel));
            }

            return [
                'title' => (string) ($exp->position ?? $exp->title ?? ''),
                'company' => (string) ($exp->company ?? ''),
                'location' => (string) ($exp->location ?? ''),
                'start' => $start,
                'end' => $end,
                'summary' => (string) ($exp->summary ?? ''),
                'bullets' => $this->splitDescription((string) ($exp->description ?? $exp->details ?? '')),
                'projects' => $roleProjects->values()->all(),
            ];
        })->values()->all();

        $education = ($resume->educations ?? collect())->map(function ($edu) use ($presentLabel) {
            $grad = '';
            if ($edu->is_present ?? false) {
                $grad = $presentLabel;
            } elseif ($edu->end_date ?? null) {
                try {
                    $grad = (string) Carbon::parse($edu->end_date)->year;
                } catch (\Throwable) {
                    $grad = '';
                }
            }

            return [
                'degree' => (string) ($edu->degree ?? ''),
                'school' => (string) ($edu->institution ?? $edu->school ?? ''),
                'location' => (string) ($edu->location ?? ''),
                'graduated' => $grad,
                'details' => (string) ($edu->description ?? ''),
            ];
        })->values()->all();

        $skills = ($resume->skills ?? collect())
            ->map(function ($skill) {
                if (is_string($skill)) {
                    return $skill;
                }
                $name = $skill->name ?? '';
                if (! $name) {
                    return null;
                }
                $prof = $skill->proficiency ?? null;

                return $prof ? "{$name} ({$prof})" : $name;
            })
            ->filter()
            ->values()
            ->all();

        $certifications = ($resume->certificates ?? collect())
            ->map(function ($cert) use ($localeTag) {
                $name = $cert->name ?? '';
                if (! $name) {
                    return null;
                }
                $issuer = $cert->issuer ?? '';
                $label = trim(implode(' — ', array_filter([$name, $issuer])));
                if ($cert->date_obtained ?? null) {
                    $d = $this->formatDate($cert->date_obtained, $localeTag);

                    return $d ? "{$label} ({$d})" : $label;
                }

                return $label;
            })
            ->filter()
            ->values()
            ->all();

        $interests = ($resume->hobbies ?? collect())
            ->map(fn ($h) => $h->name ?? null)
            ->filter()
            ->values()
            ->all();

        $languages = ($resume->languages ?? collect())
            ->map(function ($lang) {
                $l = $lang->language ?? '';
                if (! $l) {
                    return null;
                }
                $p = $lang->proficiency ?? null;

                return $p ? "{$l} ({$p})" : $l;
            })
            ->filter()
            ->values()
            ->all();

        $projects = $rawProjects
            ->filter(fn ($p) => ! ($p->experience_id ?? null))
            ->map(fn ($p) => $this->formatProject($p, $localeTag, $presentLabel))
            ->values()
            ->all();

        return [
            'name' => (string) ($basic?->full_name ?? $resume->name ?? ''),
            'tagline' => (string) ($basic?->job_title ?? ''),
            'template_layout' => $templateLayout,
            'template_id' => $resume->template_id,
            'contact' => $contact,
            'summary' => (string) ($basic?->professional_summary ?? ''),
            'experience' => $experience,
            'education' => $education,
            'skills' => $skills,
            'certifications' => $certifications,
            'interests' => $interests,
            'languages' => $languages,
            'projects' => $projects,
            'section_order' => $resume->section_order,
            'typography' => $resume->typography,
        ];
    }

    private function deriveTemplateLayout($template): string
    {
        $identifier = strtolower(trim((string) (($template->slug ?? '').' '.($template->name ?? ''))));
        if ($identifier === '') {
            return 'classic';
        }
        foreach (['executive', 'sales', 'split', 'johnathon', 'watson'] as $kw) {
            if (str_contains($identifier, $kw)) {
                return 'executive-split';
            }
        }
        foreach (['modern', 'professional', 'freelance', 'developer'] as $kw) {
            if (str_contains($identifier, $kw)) {
                return 'modern-professional';
            }
        }

        return 'classic';
    }

    private function formatDate(?string $value, string $localeTag): string
    {
        if (! $value) {
            return '';
        }
        try {
            return Carbon::parse($value)->locale(str_starts_with($localeTag, 'fr') ? 'fr' : 'en')
                ->translatedFormat('F Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatProject($project, string $localeTag, string $presentLabel): array
    {
        $start = $this->formatDate($project->startDate ?? $project->start_date ?? null, $localeTag);
        $endRaw = $project->endDate ?? $project->end_date ?? null;
        $end = $endRaw ? $this->formatDate($endRaw, $localeTag) : ($start ? $presentLabel : '');

        return [
            'name' => (string) ($project->name ?? ''),
            'description' => (string) ($project->description ?? ''),
            'technologies' => (string) ($project->technologies ?? ''),
            'url' => (string) ($project->url ?? ''),
            'start' => $start,
            'end' => $end,
            'bullets' => $this->splitDescription((string) ($project->description ?? '')),
        ];
    }

    /**
     * @return list<string>
     */
    private function splitDescription(string $text): array
    {
        $lines = preg_split('/\R+/', $text) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = preg_replace('/^[-•]\s*/', '', trim($line));
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }
}
