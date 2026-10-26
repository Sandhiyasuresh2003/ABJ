<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ABJ Builders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { navy: { 950:'#0B2F23', 900:'#104735', 800:'#155C42', 700:'#1A704F' }, gold: { 400:'#DCFCE7', 500:'#15803D', 600:'#166534' }, amber: { 50:'#F0FDF4', 100:'#DCFCE7', 200:'#BBF7D0', 700:'#15803D', 800:'#166534', 900:'#14532D' } },
                boxShadow: { soft: '0 8px 28px rgba(11, 47, 35, .07)' }
            }}
        }
    </script>
    <style>
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .nav-link { transition: .18s ease; }
        .nav-link:hover { background: rgba(255,255,255,.08); }
        .nav-link.active { color: #DCFCE7; background: rgba(21,128,61,.2); border-left: 3px solid #15803D; }
        .scrollbar-hide::-webkit-scrollbar { display:none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
<div class="min-h-screen lg:flex">
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-navy-950 text-white transition-transform duration-200 lg:static lg:translate-x-0">
        <div class="flex h-20 items-center border-b border-white/10 px-6">
            <img src="{{ asset('images/abj-builders-logo.png') }}" alt="ABJ Builders" class="h-16 w-full object-contain">
            <button id="closeSidebar" class="ml-auto rounded-lg p-2 text-slate-300 hover:bg-white/10 lg:hidden" aria-label="Close menu">✕</button>
        </div>

        <div class="px-4 pt-6">
            <p class="px-3 pb-3 text-[10px] font-bold uppercase tracking-[.2em] text-slate-500">Workspace</p>
            <nav class="space-y-1">
                <a href="#dashboard" class="nav-link active flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium" data-section="dashboard"><span>▦</span> Dashboard</a>
                <a href="#employees" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-slate-300" data-section="employees"><span>♙</span> Employees <span class="ml-auto rounded-md bg-white/10 px-2 py-0.5 text-xs">48</span></a>
                <a href="#attendance" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-slate-300" data-section="attendance"><span>◷</span> Attendance</a>
                <a href="#leave" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-slate-300" data-section="leave"><span>▣</span> Leave & Permissions <span class="ml-auto rounded-md bg-gold-500 px-2 py-0.5 text-xs font-bold text-navy-950">5</span></a>
                <a href="#late" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-slate-300" data-section="late"><span>◴</span> Late Timing</a>
                <a href="#petty-cash" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-slate-300" data-section="petty-cash"><span>₹</span> Petty Cash</a>
            </nav>
        </div>

        <div class="px-4 pt-8">
            <p class="px-3 pb-3 text-[10px] font-bold uppercase tracking-[.2em] text-slate-500">Management</p>
            <nav class="space-y-1">
                <a href="#reports" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-slate-300" data-section="reports"><span>▤</span> Reports</a>
                <a href="#settings" class="nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm text-slate-300" data-section="settings"><span>⚙</span> Settings</a>
            </nav>
        </div>

        <div class="mt-auto p-4">
            <div class="rounded-xl border border-white/10 bg-white/[.04] p-4">
                <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-400"></span><span class="text-xs font-semibold text-slate-200">System overview</span></div>
                <p class="mt-2 text-xs leading-5 text-slate-400">Dashboard uses illustrative sample data. Connect your database before using for live operations.</p>
            </div>
            <div class="mt-4 flex items-center gap-3 border-t border-white/10 px-2 pt-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gold-500 font-bold text-navy-950">AD</div>
                <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">Admin User</p><p class="text-xs text-slate-400">Administrator</p></div>
                <button class="rounded-lg p-2 text-slate-400 hover:bg-white/10" title="Profile options">•••</button>
            </div>
        </div>
    </aside>

    <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-navy-950/50 lg:hidden"></div>

    <main class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-7 xl:px-10">
            <div class="flex items-center gap-3">
                <button id="openSidebar" class="rounded-lg border border-slate-200 p-2 text-slate-600 lg:hidden" aria-label="Open menu">☰</button>
                <div><p class="text-xs font-medium text-slate-400">ABJ Builders / <span id="breadcrumb">Dashboard</span></p><h1 id="pageTitle" class="mt-1 text-lg font-bold text-navy-950 sm:text-xl">Good morning, Admin <span class="text-gold-600">✦</span></h1></div>
            </div>
            <div class="flex items-center gap-2 sm:gap-4">
                <div class="hidden text-right sm:block"><p class="text-sm font-semibold text-navy-950" id="todayDate">Tuesday, 20 October 2026</p><p class="text-xs text-slate-400">Daily overview</p></div>
                <button class="relative rounded-xl border border-slate-200 p-2.5 text-slate-600 hover:bg-slate-50" aria-label="Notifications"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-gold-500 ring-2 ring-white"></span></button>
                <div class="hidden h-9 w-px bg-slate-200 sm:block"></div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-navy-900 text-sm font-bold text-gold-400">AD</div>
            </div>
        </header>

        <div class="mx-auto max-w-[1600px] space-y-6 p-4 sm:p-7 xl:p-10">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div><div class="mb-2 inline-flex items-center gap-2 rounded-full border border-gold-500/30 bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-amber-800"><span class="h-1.5 w-1.5 rounded-full bg-gold-500"></span> Admin overview</div><h2 class="text-2xl font-bold tracking-tight text-navy-950 sm:text-3xl">Your business at a glance</h2><p class="mt-1 text-sm text-slate-500">Monitor your team, approvals and office cash in one place.</p></div>
                <div class="flex flex-wrap gap-2"><button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">↧ <span>Export / Print</span></button><button onclick="showToast('Add employee form can be connected to your employee module.')" class="inline-flex items-center gap-2 rounded-xl bg-navy-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-navy-800"><span class="text-gold-400">＋</span> Add employee</button></div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"><strong>Demo data:</strong> All employee counts, names, attendance records and petty cash amounts on this page are illustrative sample data, not live company information.</div>

            <section id="dashboard" class="grid grid-cols-1 gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                @foreach ($stats as $stat)
                    @php
                        $toneClasses = ['blue' => 'bg-green-50 text-green-800', 'green' => 'bg-emerald-50 text-emerald-700', 'amber' => 'bg-green-50 text-green-800', 'purple' => 'bg-green-50 text-green-800'];
                        $icons = ['users' => '♙', 'check' => '✓', 'clock' => '◷', 'calendar' => '▣'];
                    @endphp
                    <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-soft transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between"><div class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</div><div class="flex h-10 w-10 items-center justify-center rounded-xl text-lg font-bold {{ $toneClasses[$stat['tone']] }}">{{ $icons[$stat['icon']] }}</div></div>
                        <div class="mt-4 flex items-end justify-between"><div><div class="text-3xl font-bold tracking-tight text-navy-950">{{ $stat['value'] }}</div><div class="mt-1 text-xs text-slate-400">{{ $stat['detail'] }}</div></div><div class="h-8 w-16 text-gold-600"><svg viewBox="0 0 64 28" fill="none"><path d="M2 23L13 17L23 19L34 8L44 13L54 4L62 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div></div>
                    </article>
                @endforeach
            </section>

            <section class="grid grid-cols-1 gap-6 2xl:grid-cols-3">
                <article id="attendance" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-soft 2xl:col-span-2 sm:p-6">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h3 class="font-bold text-navy-950">Attendance overview</h3><p class="mt-1 text-xs text-slate-500">Today's attendance distribution · sample figures</p></div><button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50" onclick="showToast('Connect attendance records to enable date filtering.')">Today⌄</button></div>
                    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-5 md:items-center">
                        <div class="flex justify-center md:col-span-2"><div class="relative flex h-44 w-44 items-center justify-center rounded-full" style="background: conic-gradient(#16876B 0% 81.25%, #15803D 81.25% 89.58%, #E2E8F0 89.58% 100%)"><div class="flex h-32 w-32 flex-col items-center justify-center rounded-full bg-white"><span class="text-3xl font-bold text-navy-950">81%</span><span class="mt-1 text-xs text-slate-400">Present today</span></div></div></div>
                        <div class="space-y-4 md:col-span-3">
                            <div><div class="mb-2 flex items-center justify-between text-sm"><span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-emerald-600"></span>Present</span><strong class="text-navy-950">39 <span class="font-normal text-slate-400">(81.25%)</span></strong></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-600" style="width:81.25%"></div></div></div>
                            <div><div class="mb-2 flex items-center justify-between text-sm"><span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-gold-500"></span>Late arrivals</span><strong class="text-navy-950">4 <span class="font-normal text-slate-400">(8.33%)</span></strong></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gold-500" style="width:8.33%"></div></div></div>
                            <div><div class="mb-2 flex items-center justify-between text-sm"><span class="flex items-center gap-2 text-slate-600"><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>Absent / not checked in</span><strong class="text-navy-950">5 <span class="font-normal text-slate-400">(10.42%)</span></strong></div><div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-slate-300" style="width:10.42%"></div></div></div>
                        </div>
                    </div>
                    <div class="mt-6 grid grid-cols-3 gap-3 border-t border-slate-100 pt-5"><div><p class="text-xs text-slate-400">Expected</p><p class="mt-1 text-lg font-bold text-navy-950">48</p></div><div><p class="text-xs text-slate-400">Checked in</p><p class="mt-1 text-lg font-bold text-navy-950">43</p></div><div><p class="text-xs text-slate-400">Attendance rate</p><p class="mt-1 text-lg font-bold text-emerald-700">89.58%</p></div></div>
                </article>

                <article id="petty-cash" class="rounded-2xl bg-navy-950 p-5 text-white shadow-soft sm:p-6">
                    <div class="flex items-start justify-between"><div><p class="text-sm font-medium text-slate-300">Petty cash balance</p><p class="mt-2 text-3xl font-bold tracking-tight">₹{{ number_format($cash['balance']) }}</p><p class="mt-1 text-xs text-slate-400">Illustrative available cash</p></div><div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-xl text-gold-400">₹</div></div>
                    <div class="mt-6 grid grid-cols-2 gap-3"><div class="rounded-xl border border-white/10 bg-white/[.06] p-3"><p class="text-xs text-slate-400">Cash received</p><p class="mt-2 font-bold text-emerald-300">+₹{{ number_format($cash['received']) }}</p></div><div class="rounded-xl border border-white/10 bg-white/[.06] p-3"><p class="text-xs text-slate-400">Cash spent</p><p class="mt-2 font-bold text-rose-300">−₹{{ number_format($cash['spent']) }}</p></div></div>
                    <div class="mt-5 flex items-center justify-between border-t border-white/10 pt-4"><span class="text-xs text-slate-400">Recent transactions</span><button class="text-xs font-semibold text-gold-400 hover:text-gold-300" onclick="showToast('Connect petty cash transactions to open the full ledger.')">View ledger →</button></div>
                    <div class="mt-3 space-y-3">
                        @foreach (array_slice($cash['transactions'], 0, 2) as $transaction)
                            <div class="flex items-center gap-3"><div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-gold-400">{{ $transaction['amount'] > 0 ? '+' : '−' }}</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $transaction['description'] }}</p><p class="mt-0.5 text-xs text-slate-400">{{ $transaction['date'] }}</p></div><p class="text-sm font-semibold {{ $transaction['amount'] > 0 ? 'text-emerald-300' : 'text-slate-200' }}">{{ $transaction['amount'] > 0 ? '+' : '−' }}₹{{ number_format(abs($transaction['amount'])) }}</p></div>
                        @endforeach
                    </div>
                </article>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-5">
                <article id="late" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-soft xl:col-span-3 sm:p-6">
                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h3 class="font-bold text-navy-950">Late arrivals</h3><p class="mt-1 text-xs text-slate-500">Employees who checked in after the sample 9:00 AM start time</p></div><button class="text-xs font-semibold text-gold-600 hover:text-gold-700" onclick="showToast('Connect attendance data to open the full report.')">View attendance →</button></div>
                    <div class="mt-5 overflow-x-auto"><table class="w-full min-w-[480px] text-left"><thead><tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400"><th class="pb-3 font-semibold">Employee</th><th class="pb-3 font-semibold">Department</th><th class="pb-3 font-semibold">Check-in</th><th class="pb-3 font-semibold">Status</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @foreach ($attendance as $person)
                        <tr class="employee-row"><td class="py-3"><div class="flex items-center gap-3"><div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-navy-800">{{ $person['initials'] }}</div><span class="whitespace-nowrap text-sm font-semibold text-slate-700">{{ $person['name'] }}</span></div></td><td class="py-3 text-sm text-slate-500">{{ $person['department'] }}</td><td class="py-3 text-sm font-medium text-slate-700">{{ $person['check_in'] }}</td><td class="py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $person['tone'] === 'amber' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $person['status'] }}</span></td></tr>
                    @endforeach
                    </tbody></table></div>
                </article>

                <article id="leave" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-soft xl:col-span-2 sm:p-6">
                    <div class="flex items-center justify-between"><div><h3 class="font-bold text-navy-950">Pending approvals</h3><p class="mt-1 text-xs text-slate-500">Leave & permission requests</p></div><span class="rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">5 pending</span></div>
                    <div class="mt-4 space-y-1">
                        @foreach ($requests as $request)
                            <div class="flex items-start gap-3 rounded-xl p-3 transition hover:bg-slate-50"><div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-50 text-green-800 text-xs font-bold">{{ $request['initials'] }}</div><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><p class="text-sm font-semibold text-navy-950">{{ $request['name'] }}</p><span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ $request['type'] }}</span></div><p class="mt-1 text-xs text-slate-500">{{ $request['date'] }}</p><p class="mt-1 text-[11px] text-slate-400">{{ $request['submitted'] }}</p><div class="mt-3 flex gap-2"><button class="rounded-lg bg-navy-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-navy-800" onclick="showToast('Approval action is a demo. Connect request storage and authorization first.')">Review request</button><button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100" onclick="showToast('Request details can be connected to your leave module.')">Details</button></div></div></div>
                        @endforeach
                    </div>
                </article>
            </section>

            <section id="employees" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-soft sm:p-6">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h3 class="font-bold text-navy-950">Employee directory preview</h3><p class="mt-1 text-xs text-slate-500">Quick view of today's attendance records</p></div><div class="flex gap-2"><div class="relative"><input id="employeeSearch" type="search" placeholder="Search employees..." class="w-full rounded-xl border border-slate-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 sm:w-56"><span class="absolute left-3 top-2.5 text-slate-400">⌕</span></div><button class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" onclick="showToast('Connect employee CRUD to add or edit employee records.')">Manage</button></div></div>
                <div class="mt-5 overflow-x-auto"><table class="w-full min-w-[600px] text-left"><thead><tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400"><th class="pb-3 font-semibold">Employee</th><th class="pb-3 font-semibold">Department</th><th class="pb-3 font-semibold">Check-in</th><th class="pb-3 font-semibold">Attendance status</th></tr></thead><tbody id="employeeTable" class="divide-y divide-slate-100">
                @foreach ($attendance as $person)
                    <tr class="employee-row"><td class="py-3"><div class="flex items-center gap-3"><div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-navy-800">{{ $person['initials'] }}</div><span class="text-sm font-semibold text-slate-700">{{ $person['name'] }}</span></div></td><td class="py-3 text-sm text-slate-500">{{ $person['department'] }}</td><td class="py-3 text-sm text-slate-600">{{ $person['check_in'] }}</td><td class="py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $person['tone'] === 'amber' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $person['status'] }}</span></td></tr>
                @endforeach
                </tbody></table></div>
            </section>

            <footer class="flex flex-col justify-between gap-2 border-t border-slate-200 py-5 text-xs text-slate-400 sm:flex-row"><p>© 2026 ABJ Builders · Admin Management Portal</p><p>Prototype dashboard · Illustrative data only</p></footer>
        </div>
    </main>
</div>

<div id="toast" class="fixed bottom-5 right-5 z-50 hidden max-w-sm rounded-xl bg-navy-950 px-5 py-4 text-sm text-white shadow-xl"><div class="flex items-start gap-3"><span class="text-gold-400">ⓘ</span><p id="toastMessage"></p><button onclick="hideToast()" class="ml-2 text-slate-400">✕</button></div></div>
<script>
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    document.getElementById('openSidebar').addEventListener('click', () => { sidebar.classList.remove('-translate-x-full'); backdrop.classList.remove('hidden'); });
    document.getElementById('closeSidebar').addEventListener('click', closeSidebar);
    backdrop.addEventListener('click', closeSidebar);
    function closeSidebar() { sidebar.classList.add('-translate-x-full'); backdrop.classList.add('hidden'); }

    document.querySelectorAll('.nav-link[data-section]').forEach(link => {
        link.addEventListener('click', event => {
            document.querySelectorAll('.nav-link').forEach(item => item.classList.remove('active'));
            link.classList.add('active');
            const label = link.textContent.trim().replace(/\s+/g, ' ');
            document.getElementById('breadcrumb').textContent = label;
            if (link.dataset.section !== 'dashboard') {
                const section = document.getElementById(link.dataset.section);
                if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                else { event.preventDefault(); showToast(label + ' page is planned for the next module.'); }
            } else { window.scrollTo({ top: 0, behavior: 'smooth' }); }
            if (window.innerWidth < 1024) closeSidebar();
        });
    });

    document.getElementById('employeeSearch').addEventListener('input', event => {
        const query = event.target.value.toLowerCase().trim();
        document.querySelectorAll('#employeeTable .employee-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
    });

    let toastTimer;
    function showToast(message) {
        const toast = document.getElementById('toast');
        document.getElementById('toastMessage').textContent = message;
        toast.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 3800);
    }
    function hideToast() { document.getElementById('toast').classList.add('hidden'); }

    // Set the date using the browser's local date.
    document.getElementById('todayDate').textContent = new Intl.DateTimeFormat('en-IN', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    }).format(new Date());
</script>
</body>
</html>
