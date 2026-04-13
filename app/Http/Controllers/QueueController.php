<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\QueueService;

class QueueController extends Controller
{
    public function __construct(private readonly QueueService $queueService)
    {
    }

    public function updateStatus(Request $request, int $id, string $status): RedirectResponse
    {
        $patient = Patient::query()->findOrFail($id);

        $request->merge(['status' => $status]);
        $request->validate([
            'status' => ['required', 'string', Rule::in(['waiting', 'serving', 'done', 'no-show'])],
        ]);

        $newStatus = $request->string('status')->toString();

        DB::transaction(function () use ($patient, $newStatus) {
            $this->queueService->applyTransition($patient, $newStatus);
        });

        return redirect()
            ->back()
            ->with('success', 'Status updated.');
    }
}

