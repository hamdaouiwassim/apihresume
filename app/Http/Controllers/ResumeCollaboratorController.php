<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\ResumeCollaborator;
use App\Models\User;
use App\Mail\ResumeCollaborationInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ResumeCollaboratorController extends Controller
{
    /**
     * Invite a user to collaborate on a resume via email
     */
    public function invite(Request $request, $resumeId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $resume = Resume::findOrFail($resumeId);
            $owner = auth()->user();

            // Check if the user is verified
            if (!$owner->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your email address before inviting collaborators to your resume'
                ], 403);
            }

            // Check if the authenticated user owns the resume
            if ($resume->user_id !== $owner->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Don't allow inviting yourself
            if ($request->email === $owner->email) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot invite yourself to collaborate'
                ], 422);
            }

            // Check if user exists
            $invitedUser = User::where('email', $request->email)->first();

            // Check if already a collaborator
            if ($invitedUser) {
                $existingCollaborator = ResumeCollaborator::where('resume_id', $resumeId)
                    ->where('user_id', $invitedUser->id)
                    ->first();

                if ($existingCollaborator && $existingCollaborator->is_active) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This user is already a collaborator on this resume'
                    ], 422);
                }
            }

            // Create or update collaboration record
            $token = ResumeCollaborator::generateToken();
            
            $collaborator = ResumeCollaborator::updateOrCreate(
                [
                    'resume_id' => $resumeId,
                    'invited_email' => $request->email,
                ],
                [
                    'user_id' => $invitedUser?->id,
                    'invitation_token' => $token,
                    'invited_at' => now(),
                    'accepted_at' => null,
                    'is_active' => true,
                ]
            );

            // Generate frontend URL
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $acceptUrl = rtrim($frontendUrl, '/') . "/collaborate/accept/{$token}";

            // Send invitation email
            Mail::to($request->email)->queue(new ResumeCollaborationInvitation(
                owner: $owner,
                resume: $resume,
                acceptUrl: $acceptUrl,
                token: $token
            ));

            return response()->json([
                'status' => true,
                'message' => 'Invitation sent successfully',
                'data' => [
                    'collaborator' => $collaborator,
                    'invited_email' => $request->email,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept an invitation to collaborate
     */
    public function accept(Request $request, $token)
    {
        try {
            $collaborator = ResumeCollaborator::where('invitation_token', $token)
                ->where('is_active', true)
                ->firstOrFail();

            if (!$collaborator->isInvitationValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This invitation has expired or already been accepted'
                ], 410);
            }

            // If user is not logged in, they need to register/login first
            if (!auth()->check()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please log in to accept this invitation',
                    'requires_auth' => true
                ], 401);
            }

            $user = auth()->user();

            // Verify the email matches
            if ($user->email !== $collaborator->invited_email) {
                return response()->json([
                    'status' => false,
                    'message' => 'This invitation was sent to a different email address'
                ], 403);
            }

            // Update the collaborator record
            $collaborator->update([
                'user_id' => $user->id,
                'accepted_at' => now(),
                'invitation_token' => null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Invitation accepted successfully',
                'data' => [
                    'resume_id' => $collaborator->resume_id,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired invitation',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get all collaborators for a resume
     */
    public function index(Request $request, $resumeId)
    {
        try {
            $resume = Resume::findOrFail($resumeId);

            // Check if the authenticated user owns the resume
            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $collaborators = ResumeCollaborator::where('resume_id', $resumeId)
                ->where('is_active', true)
                ->with('user')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Collaborators fetched successfully',
                'data' => $collaborators
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a collaborator from a resume
     */
    public function remove(Request $request, $resumeId, $collaboratorId)
    {
        try {
            $resume = Resume::findOrFail($resumeId);

            // Check if the authenticated user owns the resume
            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $collaborator = ResumeCollaborator::where('resume_id', $resumeId)
                ->where('id', $collaboratorId)
                ->firstOrFail();

            $collaborator->update(['is_active' => false]);

            return response()->json([
                'status' => true,
                'message' => 'Collaborator removed successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
