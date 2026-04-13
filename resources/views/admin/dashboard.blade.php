@extends('layout.header')
@section('title', 'Dashboard — Waiting App')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div class="mb-2 mb-md-0">
                <h1 class="h4 mb-1">Queue dashboard</h1>
                <p class="text-muted small mb-0">Live metrics · Moving average (last 5) · Auto-refresh</p>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="badge badge-light wa-pill px-3 py-2 border">Today</span>
                <button type="button" class="btn btn-light wa-action-btn" onclick="location.reload()">
                    <i class="fas fa-arrows-rotate mr-1"></i>Refresh
                </button>
            </div>
        </div>

        <div class="row">
            {{-- Total patients + breakdown --}}
            <div class="col-12 col-md-6 col-xl-4 mb-4">
                <div class="wa-card h-100 p-3">
                    <div class="card-stats">
                        <div class="card-stats-title">Queue statistics — <span class="font-weight-600">today</span></div>
                        <div class="card-stats-items">
                            <div class="card-stats-item">
                                <div class="card-stats-item-count">{{ $waitingCount }}</div>
                                <div class="card-stats-item-label">Waiting</div>
                            </div>
                            <div class="card-stats-item">
                                <div class="card-stats-item-count">{{ $servingCount }}</div>
                                <div class="card-stats-item-label">Serving</div>
                            </div>
                            <div class="card-stats-item">
                                <div class="card-stats-item-count">{{ $doneCount }}</div>
                                <div class="card-stats-item-label">Done</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-icon shadow-primary bg-primary">
                        <i class="fas fa-people-group"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header border-0 pb-0">
                            <h4 class="mb-0">Total patients</h4>
                        </div>
                        <div class="card-body pt-2">
                            <span class="h3 mb-0 font-weight-bold">{{ $totalToday }}</span>
                            <p class="text-muted small mb-0 mt-2">Registered today (all statuses).</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Moving average service time (basis for estimates) --}}
            <div class="col-12 col-md-6 col-xl-4 mb-4">
                <div class="wa-card h-100 p-3">
                    <div class="card-chart">
                        <canvas id="balance-chart" height="80" aria-hidden="true"></canvas>
                    </div>
                    <div class="card-icon shadow-primary bg-primary">
                        <i class="fas fa-stopwatch"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header border-0 pb-0">
                            <h4 class="mb-0">Avg. service time</h4>
                        </div>
                        <div class="card-body pt-2">
                            <span class="h3 mb-0 font-weight-bold">{{ $avgServiceLabel }}</span>
                            <p class="text-muted small mb-0 mt-2">
                                Moving average of up to the <strong>last 5</strong> completed visits today (actual minutes served). Feeds the wait estimator.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Estimated wait for next patient (FCFS) --}}
            <div class="col-12 col-md-12 col-xl-4 mb-4">
                <div class="wa-card h-100 p-3">
                    <div class="card-chart">
                        <canvas id="sales-chart" height="80" aria-hidden="true"></canvas>
                    </div>
                    <div class="card-icon shadow-primary bg-primary">
                        <i class="fas fa-clock-rotate-left"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header border-0 pb-0">
                            <h4 class="mb-0">Est. wait — next patient</h4>
                        </div>
                        <div class="card-body pt-2">
                            <span class="h3 mb-0 font-weight-bold">{{ $estWaitLabel }}</span>
                            <p class="text-muted small mb-0 mt-2">
                                For the <strong>earliest</strong> waiting patient today (first-come, first-served). Uses per-doctor moving average × people ahead in that queue.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Service report</h2>
                <div class="text-muted small">Recent service records and estimation accuracy.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="wa-card p-3">
                    <div class="text-muted small">Avg duration</div>
                    <div class="h4 mb-0 font-weight-800">{{ $avgDuration !== null ? round((float) $avgDuration, 1).' min' : '—' }}</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="wa-card p-3">
                    <div class="text-muted small">Avg estimate</div>
                    <div class="h4 mb-0 font-weight-800">{{ $avgEstimate !== null ? round((float) $avgEstimate, 1).' min' : '—' }}</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="wa-card p-3">
                    <div class="text-muted small">Avg absolute error</div>
                    <div class="h4 mb-0 font-weight-800">{{ $avgAbsError !== null ? round((float) $avgAbsError, 1).' min' : '—' }}</div>
                </div>
            </div>
        </div>

        <div class="wa-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table wa-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Token</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Duration</th>
                                <th>Estimated</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr>
                                    <td class="text-muted small">#{{ $r->id }}</td>
                                    <td><span class="badge wa-token wa-pill px-3 py-2">{{ $r->token_number ?? '—' }}</span></td>
                                    <td class="font-weight-700">{{ $r->patient_name }}</td>
                                    <td class="text-muted">{{ $r->doctor_name ?? '—' }}</td>
                                    <td class="text-muted small">{{ $r->start_time ? \Illuminate\Support\Carbon::parse($r->start_time)->format('Y-m-d H:i') : '—' }}</td>
                                    <td class="text-muted small">{{ $r->end_time ? \Illuminate\Support\Carbon::parse($r->end_time)->format('Y-m-d H:i') : '—' }}</td>
                                    <td>{{ $r->duration !== null ? $r->duration.' min' : '—' }}</td>
                                    <td class="text-muted">{{ $r->estimated_time !== null ? $r->estimated_time.' min' : '—' }}</td>
                                    <td class="text-capitalize">{{ $r->patient_status ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted">No service records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($rows->hasPages())
                <div class="card-footer text-right">
                    {{ $rows->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var refreshMs = 30000;
    setTimeout(function () { location.reload(); }, refreshMs);
})();
</script>
@endpush
