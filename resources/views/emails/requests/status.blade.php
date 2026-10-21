<h1>{{ ucfirst($requestType) }} request {{ strtolower($request->status) }}</h1>

<p>Hello {{ $request->employee->name }},</p>

@if($requestType === 'leave')
    <p>Your {{ $request->leave_type }} request from {{ $request->start_date->format('d M Y') }} to {{ $request->end_date->format('d M Y') }} is {{ strtolower($request->status) }}.</p>
@else
    <p>Your permission request for {{ $request->permission_date->format('d M Y') }}, from {{ substr($request->start_time, 0, 5) }} to {{ substr($request->end_time, 0, 5) }}, is {{ strtolower($request->status) }}.</p>
@endif

<p>Reason: {{ $request->reason }}</p>
