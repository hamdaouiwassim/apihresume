<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Verify email via signed URL.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), (string)$hash)) {
            return redirect()->away($this->verificationRedirectUrl('error'));
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->away($this->verificationRedirectUrl('success'));
    }

    /**
     * Resend verification email for authenticated users.
     */
    public function send(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'status' => true,
                'message' => 'Email already verified.',
            ], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'status' => true,
            'message' => 'Verification link sent to your email address.',
        ], 200);
    }

    private function verificationRedirectUrl(string $status): string
    {
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        return $base . '/email-verification?status=' . $status;
    }
}

