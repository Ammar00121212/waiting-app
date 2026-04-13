<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Waiting App Ticket</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; }
        .card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; }
        .muted { color: #64748b; font-size: 12px; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 6px; }
        .token { font-size: 34px; font-weight: 900; letter-spacing: 3px; margin: 8px 0 0; }
        .row { margin-top: 14px; }
        .label { font-size: 12px; color: #64748b; margin: 0 0 4px; }
        .value { font-size: 14px; font-weight: 700; margin: 0 0 10px; }
        .divider { height: 1px; background: #e2e8f0; margin: 14px 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">Waiting App — Queue Ticket</div>
        <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>

        <div class="divider"></div>

        <div class="label">Token</div>
        <div class="token">{{ $patient->token_number ?? '—' }}</div>
        <div class="muted">Status: {{ ucfirst($patient->status ?? 'waiting') }}</div>

        <div class="row">
            <div class="label">Patient</div>
            <div class="value">{{ $patient->name }}</div>

            @if ($patient->category)
                <div class="label">Category</div>
                <div class="value">{{ $patient->category->name }}</div>
            @endif

            @if ($patient->doctor)
                <div class="label">Doctor</div>
                <div class="value">{{ $patient->doctor->name }}</div>
            @endif

            <div class="label">Estimated waiting time</div>
            <div class="value">
                @if ($estimatedMinutes === null)
                    —
                @else
                    ~{{ $estimatedMinutes }} minutes
                @endif
            </div>
        </div>

        <div class="divider"></div>
        <div class="muted">Keep this ticket. Your status and estimate may change as the queue progresses.</div>
    </div>
</body>
</html>

