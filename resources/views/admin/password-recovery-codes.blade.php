@extends('layouts.app')
@section('title', 'Password recovery codes')
@section('breadcrumb', 'Password recovery codes')
@section('heading', 'Password recovery codes')
@section('content')
<section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-8">
    <h2 class="text-xl font-bold text-navy-950">Issue a recovery code</h2>
    <p class="mt-1 text-sm text-slate-500">Generate a single-use code for a user and share it with them through a trusted channel. Codes expire after 30 days. Generating a new code replaces the previous one.</p>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    @if (session('issuedRecoveryCode'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            <p class="font-semibold">Copy this code now. It will not be shown again.</p>
            <code class="mt-2 block select-all break-all rounded-lg bg-white px-3 py-2 font-mono text-base">{{ session('issuedRecoveryCode') }}</code>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.password-recovery-codes.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="user_id" class="mb-1.5 block text-sm font-semibold">Account</label>
            <select id="user_id" class="field" name="user_id" required>
                <option value="">Select account</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary">Generate recovery code</button>
    </form>
</section>
@endsection
