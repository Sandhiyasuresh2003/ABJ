<h1>{{ ucfirst($requestType) }} request pending approval</h1>

<p>Hello MD,</p>
<p>{{ $request->employee->name }} ({{ $request->employee->employee_code }}) has a pending {{ $requestType }} request.</p>

@include('emails.requests.details')

<p>
    <a href="{{ $approveUrl }}" style="display:inline-block;padding:12px 20px;background:#047857;color:#ffffff;text-decoration:none;border-radius:6px;">Approve</a>
    <a href="{{ $rejectUrl }}" style="display:inline-block;padding:12px 20px;background:#be123c;color:#ffffff;text-decoration:none;border-radius:6px;">Reject</a>
</p>
<p>Open an action and confirm your decision on the review page. These links expire in 7 days. Keep this email private because its links authorize the decision.</p>
