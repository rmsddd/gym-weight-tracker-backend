<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(
        protected RegistrationService $service
    ) {}

    /**
     * The link from the confirmation email: it creates the account. It is public on purpose: the
     * user has no account to log in with yet, and the signature already proves it came from us.
     * It ends on the Vue app's /verify-email page, which says how it went.
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $status = match (true) {
            ! $request->hasValidSignature() => 'expired',
            ! $this->service->confirm($id, $hash) => 'invalid',
            default => 'verified',
        };

        return redirect()->away(config('app.frontend_url').'/verify-email?status='.$status);
    }
}
