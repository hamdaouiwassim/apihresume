<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'last_activity' => 'datetime',
        ];
    }

    /**
     * Get all of the resumes for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    /**
     * Get the recruiter profile for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function recruiter(): HasOne
    {
        return $this->hasOne(Recruiter::class);
    }

    /**
     * Get the candidate profile for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    /**
     * Get the admin profile for the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
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
}
