<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportingService $service): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $salesComparison = $service->salesComparison($from, $to);
        $stockValuation = $service->stockValuationAndMovements($from, $to);
        $procurementSummary = $service->procurementAndApSummary($from, $to);
        $teamSla = $service->teamSlaProductivity($from, $to);

        return view('reports.index', compact(
            'from',
            'to',
            'salesComparison',
            'stockValuation',
            'procurementSummary',
            'teamSla'
        ));
    }

    public function export(Request $request, ReportingService $service, ?string $type = null): StreamedResponse
    {
        $resolvedType = strtolower(trim((string) ($type ?? $request->input('type', 'sales'))));
        if (! in_array($resolvedType, ['sales', 'stock', 'procurement', 'sla'], true)) {
            abort(422, 'Unsupported report export type.');
        }

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        return $service->exportCsv($resolvedType, $from, $to);
    }
}
