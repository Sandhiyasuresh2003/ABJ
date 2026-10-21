<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') · ABJ Builders</title>
<link rel="icon" type="image/png" href="{{ asset('images/abj-builders-logo.png') }}">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{navy:{950:'#0B2F23',900:'#104735',800:'#155C42'},gold:{400:'#DCFCE7',500:'#15803D',600:'#166534'},amber:{50:'#F0FDF4',100:'#DCFCE7',700:'#15803D',800:'#166534'}},boxShadow:{soft:'0 8px 28px rgba(11,47,35,.07)'}}}}</script>
<style>body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}.app-background{background-color:#f0fdf4;background-image:url('{{ asset('images/dashboard-background.png') }}');background-position:center top;background-size:100% auto;background-repeat:no-repeat;background-attachment:scroll}.page-intro{padding:1.25rem;border:1px solid rgba(203,213,225,.9);border-radius:1rem;background:rgba(255,255,255,.96);box-shadow:0 8px 28px rgba(11,47,35,.12)}.page-intro h2{color:#0B2F23}.page-intro p{color:#334155}.nav-item{display:flex;gap:.75rem;align-items:center;padding:.7rem .85rem;border-radius:.65rem;color:#cbd5e1;font-size:.875rem}.nav-item:hover,.nav-item.active{background:rgba(255,255,255,.08);color:#dcfce7}.field{width:100%;border:1px solid #dbe2ea;border-radius:.75rem;padding:.7rem .85rem;font-size:.875rem;background:white}.field:focus{outline:2px solid rgba(21,128,61,.18);border-color:#15803D}.btn{display:inline-flex;align-items:center;justify-content:center;border-radius:.75rem;padding:.65rem 1rem;font-size:.875rem;font-weight:600}.btn-primary{background:#104735;color:white}.btn-primary:hover{background:#155C42}.btn-light{border:1px solid #dbe2ea;color:#104735;background:white}.btn-danger{border:1px solid #fecaca;color:#b91c1c;background:white}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
<div class="min-h-screen lg:flex">
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy-950 text-white transition-transform lg:static lg:translate-x-0">
<div class="flex h-20 items-center border-b border-white/10 px-5"><img class="h-16 w-full object-contain" src="{{ asset('images/abj-builders-logo.png') }}" alt="ABJ Builders"></div>
<div class="px-3 pt-5"><p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[.2em] text-slate-500">Workspace</p>
<a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">▦ Dashboard</a>
<a class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">♙ Employees</a>
<a class="nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}" href="{{ route('attendance.index') }}">◷ Attendance</a>
<a class="nav-item {{ request()->routeIs('leaves.*') ? 'active' : '' }}" href="{{ route('leaves.index') }}">▣ Leave requests</a>
<a class="nav-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">◴ Permissions</a>
<a class="nav-item {{ request()->routeIs('petty-cash.*') ? 'active' : '' }}" href="{{ route('petty-cash.index') }}">₹ Petty cash</a></div>
<a class="nav-item {{ request()->routeIs('admin.password-recovery-codes.*') ? 'active' : '' }}" href="{{ route('admin.password-recovery-codes.index') }}">♧ Recovery codes</a>
<div class="mt-auto p-4"><div class="rounded-xl border border-white/10 bg-white/[.04] p-3 text-xs leading-5 text-slate-400">ABJ Builders · Internal management system</div>
<div class="mt-4 flex items-center gap-3 border-t border-white/10 pt-4"><div class="flex h-9 w-9 items-center justify-center rounded-full bg-white font-bold text-navy-900">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p><p class="text-xs text-slate-400">Administrator</p></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="text-xs text-slate-400 hover:text-white">Logout</button></form></div></div>
</aside>
<div id="backdrop" class="fixed inset-0 z-30 hidden bg-navy-950/50 lg:hidden"></div>
<main class="min-w-0 flex-1 app-background">
<header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-7"><div class="flex items-center gap-3"><button id="openMenu" class="rounded-lg border border-slate-200 p-2 lg:hidden">☰</button><div><p class="text-xs text-slate-400">ABJ Builders / @yield('breadcrumb','Dashboard')</p><h1 class="mt-1 font-bold text-navy-950">@yield('heading','Dashboard')</h1></div></div><span class="hidden rounded-full border border-gold-500/30 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 sm:inline">Admin workspace</span></header>
<div class="mx-auto max-w-[1600px] space-y-6 p-4 sm:p-7 xl:p-9">
@if (session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><p class="font-semibold">Please correct the following:</p><ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
<footer class="border-t border-slate-200 pt-5 text-xs text-slate-400">© {{ date('Y') }} ABJ Builders · Admin Management System</footer>
</div></main></div>
<script>const sb=document.getElementById('sidebar'),bd=document.getElementById('backdrop');document.getElementById('openMenu')?.addEventListener('click',()=>{sb.classList.remove('-translate-x-full');bd.classList.remove('hidden')});bd.addEventListener('click',()=>{sb.classList.add('-translate-x-full');bd.classList.add('hidden')});</script>
</body></html>
