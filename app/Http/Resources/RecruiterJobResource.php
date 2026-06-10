<?php

namespace App\Http\Resources;

use App\Models\RecruiterJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin RecruiterJob */
class RecruiterJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_by_user_id' => $this->created_by_user_id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'location_type' => $this->location_type,
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'office_details' => $this->office_details,
            'location_label' => $this->locationLabel(),
            'status' => $this->status,
            'application_closes_at' => $this->application_closes_at?->toIso8601String(),
            'application_deadline_label' => $this->applicationDeadlineLabel(),
            'accepting_applications' => $this->isAcceptingApplications(),
            'slug' => $this->slug,
            'company_name' => $this->company_name,
            'company_logo' => $this->company_logo,
            'company_logo_url' => $this->companyLogoUrl(),
            'company_industry' => $this->company_industry,
            'company_size' => $this->company_size,
            'company_description' => $this->company_description,
            'company_website' => $this->company_website,
            'employment_type' => $this->employment_type,
            'employment_type_label' => $this->employmentTypeLabel(),
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'salary_currency' => $this->salary_currency,
            'salary_range_label' => $this->salaryRangeLabel(),
            'required_skills' => $this->required_skills ?? [],
            'experience_min_years' => $this->experience_min_years,
            'experience_max_years' => $this->experience_max_years,
            'experience_level_label' => $this->experienceLevelLabel(),
            'education_requirements' => $this->education_requirements,
            'applications_count' => $this->whenCounted('applications'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
        ];
    }

    private function companyLogoUrl(): ?string
    {
        if (! $this->company_logo) {
            return null;
        }

        return Storage::disk('public')->url($this->company_logo);
    }
}
