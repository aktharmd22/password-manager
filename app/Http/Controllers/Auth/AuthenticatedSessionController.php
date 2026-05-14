<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // Capture submitted email before authenticate() so a failed attempt can still
        // be matched to a user (for audit log) without leaking enumeration data.
        $submittedEmail = (string) $request->input('email');

        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $userForAudit = User::where('email', $submittedEmail)->first();
            $this->audit->log('failed_login', $userForAudit, metadata: [
                'email_attempted' => $submittedEmail,
            ]);
            throw $e;
        }

        // Successful password — regenerate session to prevent fixation.
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        $this->audit->log('login', $user);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $reason = $request->input('reason'); // 'idle' for auto-logout

        if ($user) {
            $this->audit->log('logout', $user, metadata: array_filter(['reason' => $reason]));
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with($reason === 'idle' ? 'info' : 'success',
                   $reason === 'idle' ? 'Signed out due to inactivity.' : 'Signed out.');
    }
}
