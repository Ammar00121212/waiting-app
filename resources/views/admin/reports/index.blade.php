@extends('layout.header')

@section('title', 'Reports')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <div>
                <h1 class="h4 mb-1">Reports</h1>
                <div class="text-muted small">Service time history and estimation accuracy.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="wa-card p-3">
                    <div class="text-muted small">Avg duration</div>
                    <div class="h4 mb-0 font-weight-800">{{ $avgDuration !== null ? $avgDuration.' min' : '—' }}</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="wa-card p-3">
                    <div class="text-muted small">Avg estimate</div>
                    <div class="h4 mb-0 font-weight-800">{{ $avgEstimate !== null ? $avgEstimate.' min' : '—' }}</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="wa-card p-3">
                    <div class="text-muted small">Avg absolute error</div>
                    <div class="h4 mb-0 font-weight-800">{{ $avgAbsError !== null ? $avgAbsError.' min' : '—' }}</div>
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

