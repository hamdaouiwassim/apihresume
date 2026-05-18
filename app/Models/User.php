<?php

namespace App\Models;

use App\Services\AiQuotaService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Default attribute values for new models (before save).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_pro' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'avatar',
        'google_avatar',
        'is_admin',
        'is_recruiter',
        'last_activity',
        'oauth_provider',
        'google_id',
        'google_token',
        'google_refresh_token',
        'linkedin_id',
        'linkedin_avatar',
        'linkedin_token',
        'linkedin_refresh_token',
        'github_import_token',
        'github_import_login',
        'github_import_connected_at',
        'is_pro',
        'stripe_customer_id',
        'stripe_subscription_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
        'linkedin_token',
        'linkedin_refresh_token',
        'github_import_token',
        'stripe_customer_id',
        'stripe_subscription_id',
        'ai_usage_month',
        'ai_enhance_used',
        'ai_tailor_used',
        'ai_ats_used',
    ];

    protected $appends = [
        'ai_quota',
        'github_import_connected',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_recruiter' => 'boolean',
            'ai_enhance_used' => 'integer',
            'ai_tailor_used' => 'integer',
            'ai_ats_used' => 'integer',
            'last_activity' => 'datetime',
            'github_import_token' => 'encrypted',
            'github_import_connected_at' => 'datetime',
        ];
    }

    protected function githubImportConnected(): Attribute
    {
        return Attribute::get(fn (): bool => filled($this->github_import_token));
    }

    /**
     * Pro subscription flag — requires a verified email to be active.
     */
    protected function isPro(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->hasProAccess(),
            set: fn ($value) => ['is_pro' => filter_var($value, FILTER_VALIDATE_BOOLEAN)],
        );
    }

    /**
     * Whether the user has an active Pro subscription (granted and email verified).
     */
    public function hasProAccess(): bool
    {
        return (bool) ($this->attributes['is_pro'] ?? false) && $this->hasVerifiedEmail();
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('email_verified_at') && ! $user->hasVerifiedEmail()) {
                $user->attributes['is_pro'] = false;
            }
        });
    }

    /**
     * Get all of the resumes for the User
     */
    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    /**
     * Get the recruiter profile for the user
     */
    public function recruiter(): HasOne
    {
        return $this->hasOne(Recruiter::class);
    }

    /**
     * Get the candidate profile for the user
     */
    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    /**
     * Get the admin profile for the user
     */
    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * Get the user's review.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Get the cover letters for the user
     */
    public function coverLetters(): HasMany
    {
        return $this->hasMany(CoverLetter::class);
    }

    /**
     * Employment / work certificates (standalone PDF letters).
     */
    public function workCertificates(): HasMany
    {
        return $this->hasMany(WorkCertificate::class);
    }

    /**
     * Accessor for backward compatibility - recruiter_status
     */
    public function getRecruiterStatusAttribute()
    {
        return $this->recruiter?->status;
    }

    /**
     * Accessor for backward compatibility - company_name
     */
    public function getCompanyNameAttribute()
    {
        return $this->recruiter?->company_name;
    }

    /**
     * Accessor for backward compatibility - company_size
     */
    public function getCompanySizeAttribute()
    {
        return $this->recruiter?->company_size;
    }

    /**
     * Accessor for backward compatibility - industry_focus
     */
    public function getIndustryFocusAttribute()
    {
        return $this->recruiter?->industry_focus;
    }

    /**
     * Accessor for backward compatibility - hiring_focus
     */
    public function getHiringFocusAttribute()
    {
        return $this->recruiter?->hiring_focus;
    }

    /**
     * Accessor for backward compatibility - recruiter_role
     */
    public function getRecruiterRoleAttribute()
    {
        return $this->recruiter?->recruiter_role;
    }

    /**
     * Accessor for backward compatibility - recruiter_phone
     */
    public function getRecruiterPhoneAttribute()
    {
        return $this->recruiter?->recruiter_phone;
    }

    /**
     * Accessor for backward compatibility - recruiter_linkedin
     */
    public function getRecruiterLinkedinAttribute()
    {
        return $this->recruiter?->recruiter_linkedin;
    }

    /**
     * Accessor for backward compatibility - compliance_accepted
     */
    public function getComplianceAcceptedAttribute()
    {
        return $this->recruiter?->compliance_accepted;
    }

    /**
     * Accessor for backward compatibility - brand_avatar
     */
    public function getBrandAvatarAttribute()
    {
        return $this->recruiter?->brand_avatar;
    }

    /**
     * Accessor for backward compatibility - recruiter_admin_notes
     */
    public function getRecruiterAdminNotesAttribute()
    {
        return $this->recruiter?->admin_notes;
    }

    /**
     * Monthly AI usage vs limits for non-Pro users (exposed to the app for paywalls).
     *
     * @return array<string, mixed>|null
     */
    public function getAiQuotaAttribute(): ?array
    {
        if (! array_key_exists('ai_usage_month', $this->attributes)) {
            return null;
        }

        return app(AiQuotaService::class)->snapshot($this);
    }
}
