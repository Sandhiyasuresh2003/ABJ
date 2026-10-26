@extends('layouts.app')
@section('title',$title)
@section('breadcrumb',$title)
@section('heading',$title)
@section('content')
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
    <div class="border-b border-slate-100 p-5">
        <h2 class="font-bold text-navy-950">{{ $title }}</h2>
        <p class="mt-1 text-sm text-slate-500">Only records linked to your employee account are shown here. This page is view-only.</p>
    </div>
    @if(!$employee)
        <p class="p-6 text-sm text-amber-800">No employee profile is linked to your login email. Contact an administrator.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        @if($kind === 'attendance')
                            <th class="px-5 py-3">Date</th><th class="px-5 py-3">Check in</th><th class="px-5 py-3">Check out</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Notes</th>
                        @elseif($kind === 'leave')
                            <th class="px-5 py-3">Leave type</th><th class="px-5 py-3">From</th><th class="px-5 py-3">To</th><th class="px-5 py-3">Reason</th><th class="px-5 py-3">Status</th>
                        @else
                            <th class="px-5 py-3">Date</th><th class="px-5 py-3">From</th><th class="px-5 py-3">To</th><th class="px-5 py-3">Reason</th><th class="px-5 py-3">Status</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                        <tr>
                            @if($kind === 'attendance')
                                <td class="px-5 py-4 text-sm">{{ $record->attendance_date->format('d M Y') }}</td>
                                <td class="px-5 py-4 text-sm">{{ $record->check_in ? substr($record->check_in,0,5) : '—' }}</td>
                                <td class="px-5 py-4 text-sm">{{ $record->check_out ? substr($record->check_out,0,5) : '—' }}</td>
                                <td class="px-5 py-4 text-sm font-semibold">{{ $record->status }}</td>
                                <td class="px-5 py-4 text-sm">{{ $record->notes ?: '—' }}</td>
                            @elseif($kind === 'leave')
                                <td class="px-5 py-4 text-sm font-semibold">{{ $record->leave_type }}</td>
                                <td class="px-5 py-4 text-sm">{{ $record->start_date->format('d M Y') }}</td>
                                <td class="px-5 py-4 text-sm">{{ $record->end_date->format('d M Y') }}</td>
                                <td class="px-5 py-4 text-sm">{{ $record->reason }}</td>
                                <td class="px-5 py-4 text-sm font-semibold">{{ $record->status }}</td>
                            @else
                                <td class="px-5 py-4 text-sm">{{ $record->permission_date->format('d M Y') }}</td>
                                <td class="px-5 py-4 text-sm">{{ substr($record->start_time,0,5) }}</td>
                                <td class="px-5 py-4 text-sm">{{ substr($record->end_time,0,5) }}</td>
                                <td class="px-5 py-4 text-sm">{{ $record->reason }}</td>
                                <td class="px-5 py-4 text-sm font-semibold">{{ $record->status }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No personal records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $records->links() }}</div>
    @endif
</section>
@endsection
