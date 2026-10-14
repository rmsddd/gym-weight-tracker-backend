<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected EmailVerificationService $service
    ) {}

    /**
     * The link from the email. It is public on purpose: it is often opened on a phone where the
     * user is not logged in, and the signature already proves it came from us. It ends on the
     * Vue app's /verify-email page, which says how it went.
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::find($id);

        $status = match (true) {
            ! $user => 'invalid',
            ! $request->hasValidSignature() => 'expired',
            ! $this->service->verify($user, $hash) => 'invalid',
            default => 'verified',
        };

        return redirect()->away(config('app.frontend_url').'/verify-email?status='.$status);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Your email is already confirmed.');
        }

        if (! $this->service->sendLink($user)) {
            return $this->error('We could not send the email right now. Try again in a few minutes.', 503);
        }

        return $this->success(null, 'We sent you a new confirmation link.');
    }
}
