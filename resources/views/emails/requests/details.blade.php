@if($requestType === 'leave')
    <p>{{ $request->leave_type }}: {{ $request->start_date->format('d M Y') }} to {{ $request->end_date->format('d M Y') }}.</p>
@else
    <p>Permission: {{ $request->permission_date->format('d M Y') }}, {{ substr($request->start_time, 0, 5) }} to {{ substr($request->end_time, 0, 5) }}.</p>
@endif
<p>Reason: {{ $request->reason }}</p>
