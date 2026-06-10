<?php

namespace App\Http\Resources;

use App\Models\Resume;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Resume */
class RecruiterResumeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $recruiter = $request->user();
        $access = app(RecruiterResumeAccessService::class);
        $contactVisible = $recruiter && $access->contactVisible($recruiter, $this->resource);

        $user = $this->whenLoaded('user');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'template_id' => $this->template_id,
            'section_order' => $this->section_order,
            'typography' => $this->typography,
            'open_to_recruiters' => (bool) $this->open_to_recruiters,
            'recruiter_visible_at' => $this->recruiter_visible_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'contact_visible' => $contactVisible,
            'visibility_source' => $this->visibilitySource($recruiter),
            'template' => $this->whenLoaded('template', fn () => [
                'id' => $this->template?->id,
                'name' => $this->template?->name,
            ]),
            'user' => $this->whenLoaded('user', function () use ($user, $contactVisible) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'email' => $contactVisible ? $user->email : null,
                ];
            }),
            'basic_info' => $this->whenLoaded('basicInfo', function () use ($contactVisible) {
                $info = $this->basicInfo;
                if (! $info) {
                    return null;
                }

                return [
                    'full_name' => $info->full_name,
                    'professional_summary' => $info->professional_summary,
                    'email' => $contactVisible ? $info->email : null,
                    'phone' => $contactVisible ? $info->phone : null,
                    'location' => $info->location ?? $info->city,
                ];
            }),
            'skills' => $this->whenLoaded('skills'),
            'experiences' => $this->whenLoaded('experiences'),
            'educations' => $this->whenLoaded('educations'),
            'projects' => $this->whenLoaded('projects'),
            'languages' => $this->whenLoaded('languages'),
            'certificates' => $this->whenLoaded('certificates'),
            'hobbies' => $this->whenLoaded('hobbies'),
        ];
    }

    private function visibilitySource($recruiter): ?string
    {
        if (! $recruiter) {
            return null;
        }

        if ($this->open_to_recruiters) {
            return 'talent_pool';
        }

        $access = app(RecruiterResumeAccessService::class);
        if ($access->visibleTo($recruiter, $this->resource)) {
            if (\App\Models\JobApplication::query()
                ->where('resume_id', $this->id)
                ->whereHas('job', fn ($q) => $q->where('created_by_user_id', $recruiter->id))
                ->exists()) {
                return 'application';
            }

            return 'share_grant';
        }

        return null;
    }
}
