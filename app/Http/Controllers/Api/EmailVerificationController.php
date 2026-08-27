<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Re-sends the verification email to the authenticated user.
     */
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'This email address is already verified.',
                'data' => null,
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
            'data' => null,
        ]);
    }

    /**
     * Target of the signed link in the verification email.
     *
     * The 'signed' middleware on the route guarantees the id and hash were
     * issued by us and have not expired; the hash comparison then ties the
     * link to that user's current email address.
     */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::find($id);

        if (!$user || !hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return $this->redirectToFrontend('invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectToFrontend('already-verified');
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return $this->redirectToFrontend('verified');
    }

    protected function redirectToFrontend(string $status): RedirectResponse
    {
        return redirect()->away(
            rtrim(config('app.frontend_url'), '/') . '/email-verification?status=' . $status
        );
    }
}
