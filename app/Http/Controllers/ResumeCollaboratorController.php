<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\ResumeCollaborator;
use App\Models\User;
use App\Mail\ResumeCollaborationInvitation;
use App\Mail\CollaborationSignupInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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
                'allowed_sections' => ['nullable', 'array'],
                'allowed_sections.*' => ['string', 'in:basic_info,experiences,educations,skills,hobbies,certificates,languages'],
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

            // Check if user exists in database
            $invitedUser = User::where('email', $request->email)->first();
            $userExists = $invitedUser !== null;

            // Check if already a collaborator (only if user exists)
            if ($userExists) {
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

            // Get allowed sections (default to all if not provided for backward compatibility)
            $allowedSections = $request->has('allowed_sections') && is_array($request->allowed_sections)
                ? $request->allowed_sections
                : null; // null means all sections allowed (backward compatibility)

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
                    'allowed_sections' => $allowedSections,
                ]
            );

            // Generate frontend URL
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $acceptUrl = rtrim($frontendUrl, '/') . "/collaborate/accept/{$token}";

            // Send appropriate email based on whether user exists
            try {
                if ($userExists) {
                    // User exists - send normal collaboration invitation
                    Mail::to($request->email)->queue(new ResumeCollaborationInvitation(
                        owner: $owner,
                        resume: $resume,
                        acceptUrl: $acceptUrl,
                        token: $token
                    ));
                } else {
                    // User doesn't exist - send signup encouragement email
                    Mail::to($request->email)->queue(new CollaborationSignupInvitation(
                        owner: $owner,
                        resume: $resume,
                        invitedEmail: $request->email,
                        acceptUrl: $acceptUrl,
                        token: $token
                    ));
                }
            } catch (\Exception $mailError) {
                Log::warning('Failed to send collaboration invitation email', [
                    'email' => $request->email,
                    'user_exists' => $userExists,
                    'error' => $mailError->getMessage(),
                ]);
                // Continue even if email fails - the invitation is still created
            }

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

            $authUserId = auth()->id();

            // Determine if requester is owner or accepted collaborator
            $isOwner = $resume->user_id === $authUserId;
            $isCollaborator = ResumeCollaborator::where('resume_id', $resumeId)
                ->where('user_id', $authUserId)
                ->where('is_active', true)
                ->whereNotNull('accepted_at')
                ->exists();

            if (!$isOwner && !$isCollaborator) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Owners see all collaborators; collaborators only see their own record
            $collaboratorsQuery = ResumeCollaborator::where('resume_id', $resumeId)
                ->where('is_active', true)
                ->with('user');

            if (!$isOwner) {
                $collaboratorsQuery->where('user_id', $authUserId);
            }

            $collaborators = $collaboratorsQuery->get()->map(function ($collaborator) {
                return [
                    'id' => $collaborator->id,
                    'user' => $collaborator->user,
                    'invited_email' => $collaborator->invited_email,
                    'accepted_at' => $collaborator->accepted_at,
                    'allowed_sections' => $collaborator->allowed_sections,
                    'allowed_sections_list' => $collaborator->getAllowedSections(),
                ];
            });

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

    /**
     * Get pending invitations for the current user
     */
    public function getPendingInvitations(Request $request)
    {
        try {
            $user = auth()->user();

            $invitations = ResumeCollaborator::where('invited_email', $user->email)
                ->where('is_active', true)
                ->whereNull('accepted_at')
                ->whereNotNull('invitation_token')
                ->with(['resume.template', 'resume.user'])
                ->orderBy('invited_at', 'desc')
                ->get()
                ->map(function ($collaborator) {
                    return [
                        'id' => $collaborator->id,
                        'resume_id' => $collaborator->resume_id,
                        'resume' => [
                            'id' => $collaborator->resume->id,
                            'name' => $collaborator->resume->name,
                            'template' => $collaborator->resume->template,
                            'owner' => [
                                'id' => $collaborator->resume->user->id,
                                'name' => $collaborator->resume->user->name,
                                'email' => $collaborator->resume->user->email,
                            ],
                        ],
                        'invited_at' => $collaborator->invited_at,
                        'allowed_sections' => $collaborator->allowed_sections,
                        'invitation_token' => $collaborator->invitation_token,
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Pending invitations fetched successfully',
                'data' => $invitations
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
     * Accept an invitation by ID (for authenticated users)
     */
    public function acceptInvitation(Request $request, $invitationId)
    {
        try {
            $user = auth()->user();

            $collaborator = ResumeCollaborator::where('id', $invitationId)
                ->where('invited_email', $user->email)
                ->where('is_active', true)
                ->whereNull('accepted_at')
                ->whereNotNull('invitation_token')
                ->firstOrFail();

            if (!$collaborator->isInvitationValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This invitation has expired or already been accepted'
                ], 410);
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
     * Refuse/decline an invitation
     */
    public function refuseInvitation(Request $request, $invitationId)
    {
        try {
            $user = auth()->user();

            $collaborator = ResumeCollaborator::where('id', $invitationId)
                ->where('invited_email', $user->email)
                ->where('is_active', true)
                ->whereNull('accepted_at')
                ->firstOrFail();

            // Deactivate the invitation
            $collaborator->update(['is_active' => false]);

            return response()->json([
                'status' => true,
                'message' => 'Invitation declined successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid invitation',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
