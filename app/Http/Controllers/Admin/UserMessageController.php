<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OutboundEmailService;
use Illuminate\Http\Request;

class UserMessageController extends Controller
{
    public function __invoke(Request $request, User $user, OutboundEmailService $outboundEmailService)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $outbound = $outboundEmailService->queueAdminCustom(
            admin: $request->user(),
            recipient: $user,
            subject: $data['subject'],
            body: $data['message'],
        );

        return response()->json([
            'status' => true,
            'message' => 'Email queued successfully.',
            'data' => $outbound->load(['user:id,name,email', 'triggeredBy:id,name,email']),
        ]);
    }
}
