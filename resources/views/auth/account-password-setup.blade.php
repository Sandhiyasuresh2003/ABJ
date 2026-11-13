<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up password · ABJ Builders</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 p-4 text-slate-800">
    <main class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl">
        <img class="mx-auto mb-6 h-36 w-full max-w-xs object-contain" src="{{ asset('images/abj-builders-logo.png') }}" alt="ABJ Builders">
        <h1 class="text-2xl font-bold text-slate-900">Set up your password</h1>
        <p class="mt-1 text-sm text-slate-500">Create a password for {{ $user->email }}.</p>
        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ $user->email }}">
            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold">Password</label>
                <input id="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm" type="password" name="password" required autofocus autocomplete="new-password">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold">Confirm password</label>
                <input id="password_confirmation" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button class="w-full rounded-xl bg-emerald-900 px-4 py-3 font-semibold text-white">Set password</button>
        </form>
    </main>
</body>
</html>
