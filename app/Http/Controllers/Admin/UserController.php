<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AiUsageLog;
use App\Models\OutboundEmail;
use App\Models\Recruiter;
use App\Models\User;
use App\Services\AiTokenLimitService;
use App\Services\UserBanService;
use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users with their last activity
     */
    public function index(Request $request)
    {
        try {
            $perPage = AdminPagination::resolve($request);
            $search = $request->input('search');
            $role = $request->input('role');
            $verificationStatus = $request->input('verification_status'); // 'verified' or 'unverified'
            $trashed = $request->input('trashed'); // only | with

            $query = $this->usersQueryForTrashed($trashed)
                ->with(['recruiter', 'admin', 'candidate'])
                ->withCount('resumes')
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('recruiter', function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        });
                });
            }

            // Filter by role
            if ($role === 'admin') {
                $query->where('is_admin', true);
            } elseif ($role === 'recruiter') {
                $query->where('is_recruiter', true)
                    ->whereHas('recruiter', function ($q) {
                        $q->where('status', 'approved');
                    });
            } elseif ($role === 'candidate') {
                $query->where('is_admin', false)
                    ->where('is_recruiter', false);
            } elseif ($role === 'pro') {
                $query->where('is_pro', true)
                    ->whereNotNull('email_verified_at');
            }

            // Filter by email verification status
            if ($verificationStatus === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($verificationStatus === 'unverified') {
                $query->whereNull('email_verified_at');
            }

            $users = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Users fetched successfully',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single user
     */
    public function show($id)
    {
        try {
            $user = User::withTrashed()
                ->withCount('resumes')
                ->with(['bannedBy:id,name,email'])
                ->with([
                    'resumes' => function ($query) {
                        $query->withTrashed()
                            ->with('template:id,name')
                            ->latest('updated_at');
                    },
                    'recruiter',
                    'admin',
                    'candidate',
                ])
                ->findOrFail($id);

            $monthStart = now()->copy()->startOfMonth();
            $monthEnd = now()->copy()->endOfMonth();

            $usageBase = AiUsageLog::query()
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            $aiUsage = [
                'month' => now()->format('Y-m'),
                'totals' => [
                    'calls' => (clone $usageBase)->count(),
                    'prompt_tokens' => (int) (clone $usageBase)->sum('prompt_tokens'),
                    'completion_tokens' => (int) (clone $usageBase)->sum('completion_tokens'),
                    'total_tokens' => (int) (clone $usageBase)->sum('total_tokens'),
                ],
                'by_kind' => (clone $usageBase)
                    ->select([
                        'kind',
                        DB::raw('COUNT(*) as calls'),
                        DB::raw('COALESCE(SUM(total_tokens), 0) as total_tokens'),
                    ])
                    ->groupBy('kind')
                    ->orderBy('kind')
                    ->get(),
            ];

            $recentAiLogs = AiUsageLog::query()
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(15)
                ->get([
                    'id',
                    'kind',
                    'resume_id',
                    'provider',
                    'model',
                    'prompt_tokens',
                    'completion_tokens',
                    'total_tokens',
                    'created_at',
                ]);

            $tokenService = app(AiTokenLimitService::class);
            $payload = $user->toArray();
            $payload['ai_tokens'] = $tokenService->snapshot($user);
            $payload['ai_usage'] = $aiUsage;
            $payload['recent_ai_logs'] = $recentAiLogs;
            $payload['recent_outbound_emails'] = OutboundEmail::query()
                ->where('user_id', $user->id)
                ->with(['triggeredBy:id,name,email', 'resume:id,name'])
                ->orderByDesc('created_at')
                ->limit(15)
                ->get();
            $payload['ban'] = app(UserBanService::class)->banStatusPayload($user);

            return response()->json([
                'status' => true,
                'message' => 'User fetched successfully',
                'data' => $payload,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,'.$id,
                'is_admin' => 'sometimes|boolean',
                'is_pro' => 'sometimes|boolean',
                'is_recruiter' => 'sometimes|boolean',
                'recruiter_status' => 'sometimes|in:pending,approved,revoked',
                'recruiter_admin_notes' => 'sometimes|nullable|string',
                'ai_monthly_token_limit' => 'sometimes|nullable|integer|min:0|max:100000000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::with(['recruiter', 'admin'])->findOrFail($id);

            if ($request->has('is_pro') && filter_var($request->input('is_pro'), FILTER_VALIDATE_BOOLEAN)) {
                if (! $user->hasVerifiedEmail()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Only verified users can be granted Pro access.',
                    ], 422);
                }
            }

            $userPayload = $request->only([
                'name',
                'email',
                'is_admin',
                'is_pro',
                'is_recruiter',
                'ai_monthly_token_limit',
            ]);

            // Handle admin creation/deletion
            if ($request->has('is_admin')) {
                $isAdmin = filter_var($request->input('is_admin'), FILTER_VALIDATE_BOOLEAN);
                if ($isAdmin && ! $user->admin) {
                    Admin::create(['user_id' => $user->id, 'role' => 'admin']);
                } elseif (! $isAdmin && $user->admin) {
                    $user->admin->delete();
                }
            }

            // Handle recruiter status updates
            if ($request->has('recruiter_status') && $user->recruiter) {
                $status = $request->input('recruiter_status');
                $user->recruiter->update(['status' => $status]);

                if ($request->has('recruiter_admin_notes')) {
                    $user->recruiter->update(['admin_notes' => $request->input('recruiter_admin_notes')]);
                }

                if ($status === 'approved') {
                    $userPayload['is_recruiter'] = true;
                } else {
                    $userPayload['is_recruiter'] = false;
                }
            }

            if (! empty($userPayload)) {
                $user->update($userPayload);
            }

            $user->load(['recruiter', 'admin', 'candidate']);

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot delete your own account',
                ], 403);
            }

            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'User moved to trash. You can restore them anytime from deleted users.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore($user)
    {
        try {
            $user = User::onlyTrashed()->findOrFail($user);
            $user->restore();

            return response()->json([
                'status' => true,
                'message' => 'User restored successfully.',
                'data' => $user->fresh()->load(['recruiter', 'admin', 'candidate']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to restore user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forceDestroy($user)
    {
        try {
            $user = User::withTrashed()->findOrFail($user);

            if ($user->id === auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot permanently delete your own account',
                ], 403);
            }

            $user->forceDelete();

            return response()->json([
                'status' => true,
                'message' => 'User permanently deleted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to permanently delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    private function usersQueryForTrashed(?string $trashed)
    {
        return match ($trashed) {
            'only' => User::onlyTrashed(),
            'with' => User::withTrashed(),
            default => User::query(),
        };
    }
}
