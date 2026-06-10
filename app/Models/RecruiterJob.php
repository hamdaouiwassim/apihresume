<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RecruiterJob extends Model
{
    public const LOCATION_TYPES = ['remote', 'hybrid', 'onsite'];

    public const EMPLOYMENT_TYPES = [
        'full_time',
        'part_time',
        'internship',
        'freelance',
        'contract',
        'temporary',
    ];

    protected $fillable = [
        'created_by_user_id',
        'title',
        'company_name',
        'company_logo',
        'company_industry',
        'company_size',
        'company_description',
        'company_website',
        'description',
        'location',
        'location_type',
        'location_city',
        'location_country',
        'office_details',
        'employment_type',
        'salary_min',
        'salary_max',
        'salary_currency',
        'required_skills',
        'experience_min_years',
        'experience_max_years',
        'education_requirements',
        'status',
        'application_closes_at',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'education_requirements' => 'array',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'experience_min_years' => 'integer',
            'experience_max_years' => 'integer',
            'application_closes_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RecruiterJob $job) {
            $job->syncLegacyLocation();

            if ($job->application_closes_at) {
                $job->application_closes_at = Carbon::parse($job->application_closes_at)->endOfDay();
            }

            if ($job->status === 'open' && $job->application_closes_at?->isPast()) {
                $job->status = 'closed';
            }
        });
    }

    public function isAcceptingApplications(): bool
    {
        return $this->status === 'open'
            && ($this->application_closes_at === null || $this->application_closes_at->isFuture());
    }

    public function applicationDeadlineLabel(): ?string
    {
        if (! $this->application_closes_at) {
            return null;
        }

        return $this->application_closes_at->format('M j, Y');
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $n = 1;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    public function syncLegacyLocation(): void
    {
        $parts = [];
        if ($this->location_type) {
            $parts[] = match ($this->location_type) {
                'remote' => 'Remote',
                'hybrid' => 'Hybrid',
                'onsite' => 'On-site',
                default => $this->location_type,
            };
        }
        $geo = trim(implode(', ', array_filter([$this->location_city, $this->location_country])));
        if ($geo !== '') {
            $parts[] = $geo;
        }
        if ($parts !== []) {
            $this->location = implode(' · ', $parts);
        }
    }

    public function locationLabel(): ?string
    {
        return $this->location ?: null;
    }

    public function employmentTypeLabel(): ?string
    {
        return match ($this->employment_type) {
            'full_time' => 'Full-time',
            'part_time' => 'Part-time',
            'internship' => 'Internship',
            'freelance' => 'Freelance',
            'contract' => 'Contract',
            'temporary' => 'Temporary',
            default => null,
        };
    }

    public function experienceLevelLabel(): ?string
    {
        $min = $this->experience_min_years;
        $max = $this->experience_max_years;
        if ($min === null && $max === null) {
            return null;
        }
        if ($min !== null && $max !== null) {
            return "{$min}–{$max} years";
        }
        if ($min !== null) {
            return "{$min}+ years";
        }

        return "Up to {$max} years";
    }

    /**
     * Plain text for matching / search (legacy jobs may still be a single string in DB before cast).
     */
    public function educationRequirementsText(): string
    {
        $value = $this->education_requirements;
        if (is_array($value)) {
            return implode(' ', $value);
        }

        return is_string($value) ? $value : '';
    }

    public function salaryRangeLabel(): ?string
    {
        if ($this->salary_min === null && $this->salary_max === null) {
            return null;
        }
        $currency = $this->salary_currency ?: 'USD';
        $fmt = fn ($n) => number_format((float) $n, 0);
        if ($this->salary_min !== null && $this->salary_max !== null) {
            return "{$currency} {$fmt($this->salary_min)} – {$fmt($this->salary_max)}";
        }
        if ($this->salary_min !== null) {
            return "From {$currency} {$fmt($this->salary_min)}";
        }

        return "Up to {$currency} {$fmt($this->salary_max)}";
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }
}
