<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Ticket — Waiting App</title>

    <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/modules/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/waiting-app.css') }}">
</head>
<body class="wa-page">
    @php
        $st = strtolower((string) ($patient->status ?? 'waiting'));
        $status = match ($st) {
            'waiting' => ['bg' => 'bg-warning', 'label' => 'Waiting'],
            'serving' => ['bg' => 'bg-primary', 'label' => 'Serving'],
            'done' => ['bg' => 'bg-success', 'label' => 'Done'],
            'no-show' => ['bg' => 'bg-secondary', 'label' => 'No-Show'],
            default => ['bg' => 'bg-light text-dark', 'label' => ucfirst($st)],
        };
    @endphp

    <div class="container px-3 py-4 py-md-5 wa-container">
        <div class="wa-card wa-card-hero">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h1 class="h4 mb-1">Your ticket</h1>
                        <div class="text-muted small">Live updates on status and ETA.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-light" href="{{ route('patient.ticket.download', $patient) }}">
                            <i class="fas fa-file-arrow-down mr-2"></i>Download token (PDF)
                        </a>
                        <a class="btn btn-light" href="{{ route('patient.register') }}">
                            <i class="fas fa-user-plus mr-2"></i>New check-in
                        </a>
                    </div>
                </div>

                <div class="wa-token-box mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="small text-white-50">Token</div>
                            <div class="wa-token-num">{{ $patient->token_number ?? '—' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="small text-white-50">Status</div>
                            <span id="statusBadge" class="badge {{ $status['bg'] }} wa-pill px-3 py-2 text-white" style="font-size:1rem;">
                                {{ $status['label'] }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="wa-card">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Patient</div>
                                <div class="font-weight-700">{{ $patient->name }}</div>
                                @if (!empty($patient->phone))
                                    <div class="text-muted small mt-2">Phone</div>
                                    <div>{{ $patient->phone }}</div>
                                @endif
                                @if ($patient->category)
                                    <div class="text-muted small mt-2">Category</div>
                                    <div class="font-weight-700">{{ $patient->category->name }}</div>
                                @endif
                                @if ($patient->doctor)
                                    <div class="text-muted small mt-2">Doctor</div>
                                    <div class="font-weight-700">{{ $patient->doctor->name }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="wa-card">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Estimated waiting time</div>
                                @if ($estimatedMinutes === null)
                                    <div id="etaValue" class="font-weight-700">—</div>
                                    <div id="etaHint" class="text-muted small mt-1">We’ll show an estimate while you’re waiting.</div>
                                @else
                                    <div id="etaValue" class="font-weight-700">~{{ $estimatedMinutes }} minutes</div>
                                    <div id="etaHint" class="text-muted small mt-1">Estimate updates as the queue moves.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4 mb-0 border-0 shadow-sm wa-radius-lg">
                    <div class="d-flex">
                        <div class="mr-3"><i class="fas fa-circle-info"></i></div>
                        <div>
                            <div class="font-weight-700">Tip</div>
                            <div class="small mb-0">If you close this page, you can come back using the same link (for today only).</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center text-muted small mt-3">
            Admin console? <a href="{{ route('staff.login') }}">Sign in</a> to manage the queue.
        </div>
    </div>

    <script>
        (function () {
            const badge = document.getElementById('statusBadge');
            const etaValue = document.getElementById('etaValue');
            const etaHint = document.getElementById('etaHint');
            const token = @json($patient->token_number ?? '—');
            const doctorName = @json(optional($patient->doctor)->name);

            let lastStatus = @json($patient->status ?? 'waiting');
            let lastNotifiedStatus = null;

            // Lightweight beep (no external file). Works on most browsers after a user gesture.
            function playBeep() {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    const o = ctx.createOscillator();
                    const g = ctx.createGain();
                    o.type = 'sine';
                    o.frequency.value = 880;
                    g.gain.value = 0.06;
                    o.connect(g);
                    g.connect(ctx.destination);
                    o.start();
                    setTimeout(() => {
                        o.stop();
                        ctx.close();
                    }, 180);
                } catch (e) {}
            }

            function statusMessage(st) {
                const s = String(st || '').toLowerCase();
                if (s === 'serving') return `It’s your turn now. Please proceed to ${doctorName ? doctorName : 'the doctor'}.`;
                if (s === 'done') return 'Your visit is marked as done.';
                if (s === 'no-show') return 'You were marked as no-show. Please register again if needed.';
                if (s === 'waiting') return 'You are still in the queue.';
                return `Status updated: ${s}`;
            }

            function showBrowserNotification(newStatus) {
                if (!('Notification' in window)) return;
                if (Notification.permission !== 'granted') return;
                if (lastNotifiedStatus === newStatus) return;

                lastNotifiedStatus = newStatus;
                const title = `Ticket ${token} — ${String(newStatus).toUpperCase()}`;
                const body = statusMessage(newStatus);

                try {
                    const n = new Notification(title, { body });
                    setTimeout(() => n.close?.(), 6000);
                } catch (e) {}
            }

            // Ask permission in a user-friendly, non-blocking way.
            async function ensureNotificationPermission() {
                if (!('Notification' in window)) return;
                if (Notification.permission === 'granted' || Notification.permission === 'denied') return;
                try {
                    await Notification.requestPermission();
                } catch (e) {}
            }

            const mapStatus = (s) => {
                const st = String(s || 'waiting').toLowerCase();
                switch (st) {
                    case 'waiting': return { cls: 'bg-warning', label: 'Waiting' };
                    case 'serving': return { cls: 'bg-primary', label: 'Serving' };
                    case 'done': return { cls: 'bg-success', label: 'Done' };
                    case 'no-show': return { cls: 'bg-secondary', label: 'No-Show' };
                    default: return { cls: 'bg-light text-dark', label: st.charAt(0).toUpperCase() + st.slice(1) };
                }
            };

            async function refreshTicket() {
                try {
                    const res = await fetch(@json(route('patient.ticket.json', $patient)), {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    if (!res.ok) return;
                    const data = await res.json();

                    // Notify only when status changes.
                    const newStatus = String(data.status || 'waiting').toLowerCase();
                    const prevStatus = String(lastStatus || 'waiting').toLowerCase();
                    if (newStatus !== prevStatus) {
                        // Notify + sound
                        showBrowserNotification(newStatus);
                        playBeep();
                        lastStatus = newStatus;
                    }

                    const st = mapStatus(data.status);
                    badge.className = 'badge pill px-3 py-2 text-white ' + st.cls;
                    badge.textContent = st.label;

                    if (data.estimated_minutes === null || data.estimated_minutes === undefined) {
                        etaValue.textContent = '—';
                        etaHint.textContent = 'We’ll show an estimate while you’re waiting.';
                    } else {
                        etaValue.textContent = `~${data.estimated_minutes} minutes`;
                        etaHint.textContent = 'Estimate updates as the queue moves.';
                    }
                } catch (e) {
                    // ignore transient network errors
                }
            }

            // Poll every 10s for near-real-time feel without websockets.
            setInterval(refreshTicket, 10000);

            // Try once at start.
            ensureNotificationPermission();
        })();
    </script>
</body>
</html>

