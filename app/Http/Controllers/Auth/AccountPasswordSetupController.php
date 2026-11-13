<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountPasswordSetupController extends Controller
{
    public function edit(Request $request, string $token): View
    {
        $user = $this->userForValidToken($request->query('email'), $token);
        abort_unless($user, 403, 'This password setup link is invalid or expired.');

        return view('auth.account-password-setup', ['user' => $user, 'token' => $token]);
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($credentials, $token): User {
            $user = $this->userForValidToken($credentials['email'], $token);
            abort_unless($user, 403, 'This password setup link is invalid or expired.');

            $user->forceFill([
                'password' => $credentials['password'],
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            return $user;
        });

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Your password has been set. You can now sign in.');
    }

    private function userForValidToken(mixed $email, string $token): ?User
    {
        if (! is_string($email)) {
            return null;
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->lockForUpdate()->first();

        if (
            ! $record
            || ! $record->created_at
            || now()->greaterThan(Carbon::parse($record->created_at)->addMinutes(config('auth.passwords.users.expire')))
            || ! Hash::check($token, $record->token)
        ) {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }
}
