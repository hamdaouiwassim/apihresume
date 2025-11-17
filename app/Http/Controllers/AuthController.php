<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\RecruiterSignupPending;
use App\Models\User;
use App\Models\Recruiter;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $payload = $request->all();
        $isRecruiterSignup = ($payload['account_type'] ?? 'candidate') === 'recruiter';

        $validator = Validator::make($payload, [
            "name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:users",
            "password" => "required|string|min:8|confirmed",
            "account_type" => "nullable|in:candidate,recruiter",
            "company_name" => "required_if:account_type,recruiter|string|max:255",
            "company_size" => "nullable|string|max:255",
            "industry_focus" => "required_if:account_type,recruiter|string|max:255",
            "hiring_focus" => "nullable|string|max:255",
            "recruiter_role" => "nullable|string|max:255",
            "recruiter_phone" => "nullable|string|max:30",
            "recruiter_linkedin" => "nullable|url|max:255",
            "compliance_accepted" => $isRecruiterSignup ? "accepted" : "nullable",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => "Validation error",
                "errors" => $validator->errors()
            ], 422);
        }

        $isRecruiterAccount = $request->input('account_type') === 'recruiter';

        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "is_recruiter" => $isRecruiterAccount,
        ]);

        if ($isRecruiterAccount) {
            $recruiter = Recruiter::create([
                "user_id" => $user->id,
                "status" => 'pending',
                "company_name" => $request->company_name,
                "company_size" => $request->company_size,
                "industry_focus" => $request->industry_focus,
                "hiring_focus" => $request->hiring_focus,
                "recruiter_role" => $request->recruiter_role,
                "recruiter_phone" => $request->recruiter_phone,
                "recruiter_linkedin" => $request->recruiter_linkedin,
                "compliance_accepted" => (bool)$request->input('compliance_accepted', false),
            ]);

            try {
                Mail::to($user->email)->send(
                    new RecruiterSignupPending($user->name, $recruiter->company_name)
                );
            } catch (\Throwable $mailError) {
                Log::warning('Failed to send recruiter signup email', [
                    'user_id' => $user->id,
                    'error' => $mailError->getMessage(),
                ]);
            }
        } else {
            // Create candidate record for regular users
            Candidate::create([
                "user_id" => $user->id,
            ]);
        }

        // Trigger email verification
        event(new Registered($user));

        $token = $user->createToken("API Token")->plainTextToken;
        $user->load(['recruiter', 'candidate', 'admin']);

        return response()->json([
            "status" => true,
            "message" => $isRecruiterAccount
                ? "Recruiter account submitted. Please verify your email. An admin will activate your access soon."
                : "Account created. Please verify your email to start using the platform.",
            "user" => $user,
            "token" => $token,
            "requires_email_verification" => true,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required|min:8"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => "Validation error",
                "errors" => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only("email", "password"))) {
            return response()->json([
                "status" => false,
                "message" => "Invalid email or password"
            ], 401);
        }

        $user = User::with(['recruiter', 'candidate', 'admin'])->where("email", $request->email)->first();
        $token = $user->createToken("API Token")->plainTextToken;

        $requiresVerification = !$user->hasVerifiedEmail();
        if ($requiresVerification) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                Log::warning('Failed to send verification email on login', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            "status" => true,
            "message" => $user->hasVerifiedEmail() ? "Login successful" : "Email verification required",
            "user" => $user,
            "token" => $token,
            "requires_email_verification" => $requiresVerification,
        ], 200);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            "status" => true,
            "message" => "Logged out successfully"
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['recruiter', 'candidate', 'admin']);
        return response()->json([
            "status" => true,
            "user" => $user
        ], 200);
    }

    /**
     * Get Google OAuth URL
     */
    public function getGoogleAuthUrl()
    {
        if (!$this->isGoogleAuthEnabled()) {
            return response()->json([
                'status' => false,
                'message' => 'Google sign-in is not configured.',
            ], 503);
        }

        try {
            $redirectUrl = $this->googleDriver()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'status' => true,
                'url' => $redirectUrl,
            ]);
        } catch (\Throwable $th) {
            Log::error('Failed to generate Google OAuth URL', [
                'error' => $th->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to initiate Google authentication at the moment.',
            ], 500);
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        if (!$this->isGoogleAuthEnabled()) {
            return $this->socialErrorRedirect('Google authentication is disabled.');
        }

        try {
            $googleUser = $this->googleDriver()->user();
        } catch (\Throwable $th) {
            Log::error('Google OAuth callback failed', [
                'error' => $th->getMessage(),
            ]);

            return $request->wantsJson()
                ? response()->json([
                    'status' => false,
                    'message' => 'Failed to authenticate with Google.',
                ], 400)
                : $this->socialErrorRedirect('Failed to authenticate with Google.');
        }

        if (!$googleUser->getEmail()) {
            return $request->wantsJson()
                ? response()->json([
                    'status' => false,
                    'message' => 'We could not retrieve your Google email address.',
                ], 422)
                : $this->socialErrorRedirect('We could not retrieve your Google email address.');
        }

        $user = User::where('email', $googleUser->getEmail())
            ->orWhere('google_id', $googleUser->getId())
            ->first();

        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Candidate',
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
                'avatar' => $googleUser->getAvatar(),
                'google_avatar' => $googleUser->getAvatar(),
                'oauth_provider' => 'google',
                'google_id' => $googleUser->getId(),
                'google_token' => $googleUser->token ?? null,
                'google_refresh_token' => $googleUser->refreshToken ?? null,
                'is_recruiter' => false,
            ]);

            // Create candidate record for new Google users
            Candidate::create([
                'user_id' => $user->id,
            ]);
        } else {
            $updateData = [
                'oauth_provider' => 'google',
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'google_token' => $googleUser->token ?? null,
                'google_refresh_token' => $googleUser->refreshToken ?? null,
            ];

            if (blank($user->avatar) && $googleUser->getAvatar()) {
                $updateData['avatar'] = $googleUser->getAvatar();
            }

            if (blank($user->name) && $googleUser->getName()) {
                $updateData['name'] = $googleUser->getName();
            }

            if (is_null($user->email_verified_at)) {
                $updateData['email_verified_at'] = now();
            }

            $user->forceFill(array_filter($updateData, fn ($value) => !is_null($value)))->save();
        }

        $token = $user->createToken('API Token')->plainTextToken;
        $requiresVerification = !$user->hasVerifiedEmail();

        if ($requiresVerification) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                Log::warning('Failed to send verification email after social login', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        $user->load(['recruiter', 'candidate', 'admin']);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token,
                'is_new_user' => $isNewUser,
                'provider' => 'google',
                'requires_email_verification' => $requiresVerification,
            ]);
        }

        $redirectUrl = $this->buildSocialRedirectUrl([
            'status' => 'success',
            'token' => $token,
            'provider' => 'google',
            'is_new_user' => $isNewUser ? '1' : '0',
            'requires_email_verification' => $requiresVerification ? '1' : '0',
        ]);

        return redirect()->away($redirectUrl);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "nullable|string|max:255",
            "email" => "nullable|string|email|max:255|unique:users,email," . $request->user()->id,
            "avatar" => "nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120", // 5MB max
            "password" => "nullable|string|min:8|confirmed",
            "company_name" => "nullable|string|max:255",
            "company_size" => "nullable|string|max:255",
            "industry_focus" => "nullable|string|max:255",
            "hiring_focus" => "nullable|string|max:255",
            "recruiter_role" => "nullable|string|max:255",
            "recruiter_phone" => "nullable|string|max:30",
            "recruiter_linkedin" => "nullable|url|max:255",
            "compliance_accepted" => "nullable|boolean",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => "Validation error",
                "errors" => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $updateData = [];
            
            // Debug: Log request info
            Log::info('Profile update request', [
                'method' => $request->method(),
                'has_file_avatar' => $request->hasFile('avatar'),
                'has_avatar' => $request->has('avatar'),
                'all_files' => array_keys($request->allFiles()),
                'all_input_keys' => array_keys($request->all()),
                'content_type' => $request->header('Content-Type'),
                'request_size' => $request->header('Content-Length'),
            ]);

            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            // Handle recruiter-specific fields
            if ($user->is_recruiter && $user->recruiter) {
                $recruiterUpdateData = [];
                $recruiterFields = [
                    'company_name',
                    'company_size',
                    'industry_focus',
                    'hiring_focus',
                    'recruiter_role',
                    'recruiter_phone',
                    'recruiter_linkedin',
                ];
                foreach ($recruiterFields as $field) {
                    if ($request->has($field)) {
                        $recruiterUpdateData[$field] = $request->$field;
                    }
                }
                if ($request->has('compliance_accepted')) {
                    $recruiterUpdateData['compliance_accepted'] = (bool) $request->compliance_accepted;
                }
                if (!empty($recruiterUpdateData)) {
                    $user->recruiter->update($recruiterUpdateData);
                }
            }
            
            // Handle avatar file upload
            if ($request->hasFile('avatar')) {
                try {
                    $file = $request->file('avatar');
                    
                    // Debug: Log file info
                    Log::info('Avatar upload attempt', [
                        'has_file' => $request->hasFile('avatar'),
                        'file_name' => $file ? $file->getClientOriginalName() : 'null',
                        'file_size' => $file ? $file->getSize() : 'null',
                        'file_mime' => $file ? $file->getMimeType() : 'null',
                        'is_valid' => $file ? $file->isValid() : false,
                    ]);
                    
                    // Validate file is actually uploaded
                    if (!$file || !$file->isValid()) {
                        throw new \Exception('Invalid file upload: ' . ($file ? $file->getError() : 'File is null'));
                    }
                    
                    // Delete old avatar if exists (only if it's stored locally)
                    if ($user->avatar) {
                        // Extract the path from the URL if it's a local storage URL
                        $storageUrl = Storage::disk('public')->url('');
                        if (str_contains($user->avatar, $storageUrl)) {
                            $oldAvatarPath = str_replace($storageUrl, '', $user->avatar);
                            // Remove leading slash if present
                            $oldAvatarPath = ltrim($oldAvatarPath, '/');
                            if (!empty($oldAvatarPath) && Storage::disk('public')->exists($oldAvatarPath)) {
                                Storage::disk('public')->delete($oldAvatarPath);
                            }
                        }
                    }
                    
                    // Ensure avatars directory exists and is writable
                    $avatarsDir = Storage::disk('public')->path('avatars');
                    if (!is_dir($avatarsDir)) {
                        Storage::disk('public')->makeDirectory('avatars', 0755, true);
                    }
                    
                    // Store new avatar with a unique name
                    // Generate a unique filename: timestamp + uniqid + extension
                    $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $avatarPath = $file->storeAs('avatars', $filename, 'public');
                    
                    // Verify file was actually stored
                    if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                        // Generate full URL using the request's scheme and host (includes port if present)
                        // This ensures the URL matches the API server the frontend is calling
                        $scheme = $request->getScheme();
                        $host = $request->getHost();
                        $port = $request->getPort();
                        $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                        $updateData['avatar'] = $baseUrl . '/storage/' . $avatarPath;
                        Log::info('Avatar URL generated', [
                            'url' => $updateData['avatar'], 
                            'path' => $avatarPath,
                            'scheme' => $scheme,
                            'host' => $host,
                            'port' => $port,
                            'base_url' => $baseUrl,
                            'storage_path' => 'storage/' . $avatarPath
                        ]);
                    } else {
                        throw new \Exception('Failed to store avatar file. Storage returned path: ' . ($avatarPath ?? 'null'));
                    }
                } catch (\Exception $e) {
                    Log::error('Avatar upload error: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'file_info' => $request->hasFile('avatar') ? [
                            'original_name' => $request->file('avatar')->getClientOriginalName(),
                            'size' => $request->file('avatar')->getSize(),
                            'mime' => $request->file('avatar')->getMimeType(),
                        ] : null,
                    ]);
                    return response()->json([
                        "status" => false,
                        "message" => "Failed to upload avatar",
                        "error" => $e->getMessage()
                    ], 500);
                }
            }

            // Handle brand avatar upload (recruiter only)
            if ($request->hasFile('brand_avatar') && $user->is_recruiter && $user->recruiter) {
                try {
                    $file = $request->file('brand_avatar');

                    if (!$file || !$file->isValid()) {
                        throw new \Exception('Invalid brand avatar upload: ' . ($file ? $file->getError() : 'File is null'));
                    }

                    if ($user->recruiter->brand_avatar) {
                        $storageUrl = Storage::disk('public')->url('');
                        if (str_contains($user->recruiter->brand_avatar, $storageUrl)) {
                            $oldPath = str_replace($storageUrl, '', $user->recruiter->brand_avatar);
                            $oldPath = ltrim($oldPath, '/');
                            if (!empty($oldPath) && Storage::disk('public')->exists($oldPath)) {
                                Storage::disk('public')->delete($oldPath);
                            }
                        }
                    }

                    $brandDir = Storage::disk('public')->path('brand-avatars');
                    if (!is_dir($brandDir)) {
                        Storage::disk('public')->makeDirectory('brand-avatars', 0755, true);
                    }

                    $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png';
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $brandPath = $file->storeAs('brand-avatars', $filename, 'public');

                    if ($brandPath && Storage::disk('public')->exists($brandPath)) {
                        $scheme = $request->getScheme();
                        $host = $request->getHost();
                        $port = $request->getPort();
                        $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
                        $user->recruiter->update(['brand_avatar' => $baseUrl . '/storage/' . $brandPath]);
                    } else {
                        throw new \Exception('Failed to store brand avatar file.');
                    }
                } catch (\Exception $e) {
                    Log::error('Brand avatar upload error: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                    ]);
                    return response()->json([
                        "status" => false,
                        "message" => "Failed to upload company logo",
                        "error" => $e->getMessage()
                    ], 500);
                }
            }
            
            if ($request->has('password') && $request->password) {
                $updateData['password'] = Hash::make($request->password);
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // Reload user with relationships
            $user->load(['recruiter', 'candidate', 'admin']);

            return response()->json([
                "status" => true,
                "message" => "Profile updated successfully",
                "user" => $user->fresh(['recruiter', 'candidate', 'admin'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => "Something went wrong",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    private function isGoogleAuthEnabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    private function googleDriver()
    {
        return Socialite::driver('google')
            ->stateless()
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account']);
    }

    private function buildSocialRedirectUrl(array $params): string
    {
        $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $path = '/auth/social-callback';
        $query = http_build_query($params);

        return $baseUrl . $path . '?' . $query;
    }

    private function socialErrorRedirect(string $message)
    {
        $redirectUrl = $this->buildSocialRedirectUrl([
            'status' => 'error',
            'message' => $message,
            'provider' => 'google',
        ]);

        return redirect()->away($redirectUrl);
    }
}
