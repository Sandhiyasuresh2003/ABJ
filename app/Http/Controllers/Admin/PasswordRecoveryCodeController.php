<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordRecoveryCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordRecoveryCodeController extends Controller
{
    public function index(): View
    {
        return view('admin.password-recovery-codes', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $code = Str::upper(Str::random(8)).'-'.Str::upper(Str::random(8)).'-'.Str::upper(Str::random(8));

        PasswordRecoveryCode::updateOrCreate(
            ['user_id' => $validated['user_id']],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addDays(30),
                'used_at' => null,
            ]
        );

        return redirect()->route('admin.password-recovery-codes.index')
            ->with('issuedRecoveryCode', $code);
    }
}
