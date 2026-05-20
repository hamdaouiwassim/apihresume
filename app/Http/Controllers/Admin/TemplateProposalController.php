<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplateProposalDecisionMail;
use App\Models\Template;
use App\Models\TemplateProposal;
use App\Support\AdminPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TemplateProposalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = AdminPagination::resolve($request);
        $query = TemplateProposal::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $proposal = TemplateProposal::findOrFail($id);

        $validated = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,approved,rejected',
            'admin_notes' => 'nullable|string|max:5000',
        ])->validate();

        $previousStatus = $proposal->status;
        $proposal->update($validated);
        $proposal->load('user:id,name,email');

        if (
            isset($validated['status'])
            && in_array($validated['status'], ['approved', 'rejected'], true)
            && $previousStatus !== $validated['status']
            && $proposal->user?->email
        ) {
            Mail::to($proposal->user->email)->send(
                new TemplateProposalDecisionMail(
                    $proposal,
                    $validated['status'],
                    $validated['admin_notes'] ?? $proposal->admin_notes
                )
            );
        }

        return response()->json(['status' => true, 'data' => $proposal]);
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $proposal = TemplateProposal::findOrFail($id);

        if ($proposal->status === 'approved') {
            return response()->json(['status' => false, 'message' => 'Already published.'], 422);
        }

        $template = Template::create([
            'name' => $proposal->name,
            'description' => $proposal->description ?? '',
            'category' => in_array($proposal->category, ['Corporate', 'Creative', 'Simple'], true)
                ? $proposal->category
                : 'Corporate',
            'preview_image_url' => $proposal->preview_image_url,
        ]);

        $proposal->update([
            'status' => 'approved',
            'admin_notes' => $request->input('admin_notes', $proposal->admin_notes),
        ]);
        $proposal->load('user:id,name,email');

        if ($proposal->user?->email) {
            Mail::to($proposal->user->email)->send(
                new TemplateProposalDecisionMail($proposal, 'approved', $proposal->admin_notes)
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Template published from proposal.',
            'data' => ['proposal' => $proposal, 'template' => $template],
        ]);
    }
}
