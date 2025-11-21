<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Recruiter;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users with their last activity
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $role = $request->input('role');
            $verificationStatus = $request->input('verification_status'); // 'verified' or 'unverified'

            $query = User::with(['recruiter', 'admin', 'candidate'])
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
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single user
     */
    public function show($id)
    {
        try {
            $user = User::withCount('resumes')
                ->with(['resumes' => function ($query) {
                    $query->with('template')->latest()->limit(5);
                }])
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'User fetched successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
                'error' => $e->getMessage()
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
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'is_admin' => 'sometimes|boolean',
                'is_recruiter' => 'sometimes|boolean',
                'recruiter_status' => 'sometimes|in:pending,approved,revoked',
                'recruiter_admin_notes' => 'sometimes|nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::with(['recruiter', 'admin'])->findOrFail($id);
            $userPayload = $request->only([
                'name',
                'email',
                'is_admin',
                'is_recruiter',
            ]);

            // Handle admin creation/deletion
            if ($request->has('is_admin')) {
                $isAdmin = filter_var($request->input('is_admin'), FILTER_VALIDATE_BOOLEAN);
                if ($isAdmin && !$user->admin) {
                    Admin::create(['user_id' => $user->id, 'role' => 'admin']);
                } elseif (!$isAdmin && $user->admin) {
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

            if (!empty($userPayload)) {
                $user->update($userPayload);
            }

            $user->load(['recruiter', 'admin', 'candidate']);

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
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
                    'message' => 'You cannot delete your own account'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

