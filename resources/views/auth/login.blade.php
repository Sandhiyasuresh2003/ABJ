<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in · ABJ Builders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config={theme:{extend:{colors:{navy:{950:'#0B2F23',900:'#104735',800:'#155C42'},gold:{400:'#DCFCE7',500:'#15803D',600:'#166534'}}}}}
    </script>
</head>

<body class="flex min-h-screen items-center justify-center bg-slate-50 p-4 text-slate-800">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl"><a href="{{ route('agropro.home') }}" class="block"><img
            class="mx-auto mb-6 h-36 w-full max-w-xs object-contain" src="{{ asset('images/abj-builders-logo.png') }}"
            alt="ABJ Builders"></a>
        <p class="-mt-4 mb-6 text-center text-xs tracking-widest text-slate-400">ABJ BUILDERS PORTAL</p>
        <h2 class="text-2xl font-bold text-navy-950">Welcome back</h2>
        <p class="mt-1 text-sm text-slate-500">Sign in to manage your company operations.</p>
        @if ($errors->any())<div
            class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{
            $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('login.submit') }}" class="mt-6 space-y-4">@csrf<div><label
                    class="mb-1.5 block text-sm font-semibold">Email address</label><input
                    class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-gold-500"
                    type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div><label class="mb-1.5 block text-sm font-semibold">Password</label><input
                    class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-gold-500"
                    type="password" name="password" required autocomplete="current-password"></div><label
                class="flex items-center gap-2 text-sm text-slate-500"><input type="checkbox" name="remember"
                    class="rounded border-slate-300"> Remember me</label><button
                class="w-full rounded-xl bg-navy-900 px-4 py-3 font-semibold text-white hover:bg-navy-800">Sign
                in</button>
        </form>
        <p class="mt-4 text-center text-sm"><a href="{{ route('password.request') }}"
                class="font-semibold text-navy-900 hover:text-gold-600">Forgot your password?</a></p>
    </div>
</body>

</html>
