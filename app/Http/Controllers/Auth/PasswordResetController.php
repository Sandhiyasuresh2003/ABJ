<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordRecoveryCode;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const RECOVERY_SESSION_USER = 'password_recovery_user_id';

    private const RECOVERY_SESSION_EXPIRY = 'password_recovery_expires_at';

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->session()->forget([
            self::RECOVERY_SESSION_USER,
            self::RECOVERY_SESSION_EXPIRY,
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'recovery_code' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $verifiedUserId = null;

        if ($user) {
            DB::transaction(function () use ($user, $credentials, &$verifiedUserId): void {
                $recoveryCode = PasswordRecoveryCode::query()
                    ->where('user_id', $user->id)
                    ->whereNull('used_at')
                    ->lockForUpdate()
                    ->first();

                if (
                    $recoveryCode
                    && $recoveryCode->expires_at->isFuture()
                    && Hash::check($credentials['recovery_code'], $recoveryCode->code_hash)
                ) {
                    $recoveryCode->forceFill(['used_at' => now()])->save();
                    $verifiedUserId = $user->id;
                }
            });
        }

        if (! $verifiedUserId) {
            throw ValidationException::withMessages([
                'recovery_code' => 'The email address or recovery code is invalid or expired.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put([
            self::RECOVERY_SESSION_USER => $verifiedUserId,
            self::RECOVERY_SESSION_EXPIRY => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->route('password.reset');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $user = $this->recoveryUser($request);

        if (! $user) {
            $this->clearRecoverySession($request);

            return redirect()->route('password.request')
                ->withErrors(['recovery_code' => 'Verify your email and recovery code to continue.']);
        }

        return view('auth.reset-password', ['user' => $user]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->recoveryUser($request);

        if (! $user) {
            $this->clearRecoverySession($request);

            return redirect()->route('password.request')
                ->withErrors(['recovery_code' => 'Your password reset session expired. Verify your recovery code again.']);
        }

        $credentials = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => $credentials['password'],
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
        $this->clearRecoverySession($request);
        $request->session()->regenerate();

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now sign in.');
    }

    private function recoveryUser(Request $request): ?User
    {
        $userId = $request->session()->get(self::RECOVERY_SESSION_USER);
        $expiresAt = $request->session()->get(self::RECOVERY_SESSION_EXPIRY);

        if (! is_int($userId) || ! is_int($expiresAt) || $expiresAt <= now()->timestamp) {
            return null;
        }

        return User::find($userId);
    }

    private function clearRecoverySession(Request $request): void
    {
        $request->session()->forget([
            self::RECOVERY_SESSION_USER,
            self::RECOVERY_SESSION_EXPIRY,
        ]);
    }
}
