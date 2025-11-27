<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminUserMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserMessageController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        Mail::to($user->email)->queue(new AdminUserMessage(
            admin: $request->user(),
            user: $user,
            subjectLine: $data['subject'],
            bodyMessage: $data['message'],
        ));

        return response()->json([
            'status' => true,
            'message' => 'Email queued successfully.',
        ]);
    }
}

