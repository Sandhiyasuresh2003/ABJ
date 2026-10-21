<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>{{ ucfirst($requestType) }} request review</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #0f172a; margin: 0; padding: 32px 16px; }
        main { max-width: 560px; margin: 0 auto; padding: 28px; background: white; border-radius: 12px; }
        p { line-height: 1.6; overflow-wrap: anywhere; }
        button { border: 0; border-radius: 6px; padding: 12px 20px; color: white; background: {{ $decision === 'approve' ? '#047857' : '#be123c' }}; cursor: pointer; font-size: 16px; }
        .error { color: #be123c; }
    </style>
</head>
<body>
<main>
    <h1>{{ ucfirst($requestType) }} request review</h1>
    <p>Employee: <strong>{{ $item->employee?->name ?? 'Deleted employee' }}</strong></p>
    @include('emails.requests.details', ['request' => $item])
    <p>Status: <strong>{{ $item->status }}</strong></p>
    @if($error)<p class="error" role="alert">{{ $error }}</p>@endif
    @if($item->status === 'Pending')
        <p>Confirm that you want to {{ $decision }} this request. The employee will receive the decision by email.</p>
        <form method="POST" action="{{ $actionUrl }}">
            @csrf
            <button type="submit">Confirm {{ $decision === 'approve' ? 'approval' : 'rejection' }}</button>
        </form>
    @else
        <p>This request has already been reviewed. No further action is needed.</p>
    @endif
</main>
</body>
</html>
