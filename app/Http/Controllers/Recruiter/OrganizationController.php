<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Recruiter;
use App\Models\RecruiterOrganization;
use App\Models\RecruiterOrganizationMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $recruiter = $request->user()->recruiter;
        if (! $recruiter?->organization_id) {
            return response()->json(['status' => true, 'data' => null]);
        }

        $org = RecruiterOrganization::with(['members.user:id,name,email'])
            ->find($recruiter->organization_id);

        return response()->json(['status' => true, 'data' => $org]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->recruiter?->organization_id) {
            return response()->json(['status' => false, 'message' => 'Organization already exists.'], 422);
        }

        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ])->validate();

        $org = RecruiterOrganization::create([
            'name' => $validated['name'],
            'owner_user_id' => $user->id,
        ]);

        RecruiterOrganizationMember::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        $user->recruiter?->update(['organization_id' => $org->id]);

        return response()->json(['status' => true, 'data' => $org], 201);
    }

    public function addMember(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = $user->recruiter?->organization_id;
        if (! $orgId) {
            return response()->json(['status' => false, 'message' => 'Create an organization first.'], 422);
        }

        $this->assertOrgAdmin($user, $orgId);

        $validated = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'role' => 'nullable|in:admin,recruiter,viewer',
        ])->validate();

        $memberUser = User::where('email', $validated['email'])->firstOrFail();
        if (! $memberUser->is_recruiter || $memberUser->recruiter?->status !== 'approved') {
            return response()->json(['status' => false, 'message' => 'User must be an approved recruiter.'], 422);
        }

        RecruiterOrganizationMember::updateOrCreate(
            ['organization_id' => $orgId, 'user_id' => $memberUser->id],
            ['role' => $validated['role'] ?? 'recruiter']
        );

        $memberUser->recruiter?->update(['organization_id' => $orgId]);

        return response()->json(['status' => true, 'message' => 'Member added.']);
    }

    private function assertOrgAdmin(User $user, int $orgId): void
    {
        $isAdmin = RecruiterOrganizationMember::query()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();

        if (! $isAdmin && RecruiterOrganization::where('id', $orgId)->where('owner_user_id', $user->id)->exists()) {
            return;
        }

        if (! $isAdmin) {
            abort(response()->json(['status' => false, 'message' => 'Admin role required.'], 403));
        }
    }
}
