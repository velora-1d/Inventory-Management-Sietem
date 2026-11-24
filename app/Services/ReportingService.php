<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\AccountsPayable;
use App\Models\AccountsPayablePayment;
use App\Models\Order;
use App\Models\OrderTask;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingService
{
    /**
     * Compare POS sales vs Customer Portal orders within an optional date range.
     */
    public function salesComparison(?string $from = null, ?string $to = null): array
    {
        [$fromAt, $toAt] = $this->normalizeDateRange($from, $to);

        $salesQuery = Sale::query()
            ->where('status', '!=', SaleStatus::CANCELLED->value);

        if ($fromAt) {
            $salesQuery->where('sale_date', '>=', $fromAt);
        }
        if ($toAt) {
            $salesQuery->where('sale_date', '<=', $toAt);
        }

        $ordersQuery = Order::query()
            ->where('status', '!=', 'cancelled');

        if ($fromAt) {
            $ordersQuery->where('created_at', '>=', $fromAt);
        }
        if ($toAt) {
            $ordersQuery->where('created_at', '<=', $toAt);
        }

        $posCount = (int) (clone $salesQuery)->count();
        $posTotal = round((float) (clone $salesQuery)->sum('total'), 2);
        $portalCount = (int) (clone $ordersQuery)->count();
        $portalTotal = round((float) (clone $ordersQuery)->sum('total'), 2);

        return [
            'from' => $from,
            'to' => $to,
            'pos_sales_count' => $posCount,
            'pos_sales_total' => $posTotal,
            'portal_orders_count' => $portalCount,
            'portal_orders_total' => $portalTotal,
            'combined_count' => $posCount + $portalCount,
            'combined_total' => round($posTotal + $portalTotal, 2),
            'pos' => [
                'count' => $posCount,
                'total' => $posTotal,
            ],
            'portal' => [
                'count' => $portalCount,
                'total' => $portalTotal,
            ],
        ];
    }

    /**
     * Calculate stock valuation (sum of on_hand * purchase_price) and movement summary by type.
     */
    public function stockValuationAndMovements(?string $from = null, ?string $to = null): array
    {
        [$fromAt, $toAt] = $this->normalizeDateRange($from, $to);

        $valuationRow = DB::table('stock_balances')
            ->join('products', 'products.id', '=', 'stock_balances.product_id')
            ->selectRaw('COALESCE(SUM(stock_balances.on_hand * products.purchase_price), 0) as stock_value')
            ->selectRaw('COALESCE(SUM(stock_balances.on_hand), 0) as total_on_hand')
            ->selectRaw('COALESCE(SUM(stock_balances.reserved), 0) as total_reserved')
            ->first();

        $totalSkus = (int) Product::query()->count();

        $movementsQuery = StockMovement::query();
        if ($fromAt) {
            $movementsQuery->where('created_at', '>=', $fromAt);
        }
        if ($toAt) {
            $movementsQuery->where('created_at', '<=', $toAt);
        }

        $movementsByType = (clone $movementsQuery)
            ->selectRaw('type, COUNT(*) as movement_count, COALESCE(SUM(qty), 0) as total_qty')
            ->groupBy('type')
            ->orderBy('type')
            ->get()
            ->map(fn ($row) => [
                'type' => (string) $row->type,
                'count' => (int) $row->movement_count,
                'total_qty' => (int) $row->total_qty,
            ])
            ->values()
            ->all();

        $recentMovements = (clone $movementsQuery)
            ->with(['product', 'warehouse'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return [
            'from' => $from,
            'to' => $to,
            'stock_value' => round((float) ($valuationRow->stock_value ?? 0), 2),
            'total_on_hand' => (int) ($valuationRow->total_on_hand ?? 0),
            'total_reserved' => (int) ($valuationRow->total_reserved ?? 0),
            'total_skus' => $totalSkus,
            'movements_by_type' => $movementsByType,
            'recent_movements' => $recentMovements,
        ];
    }

    /**
     * Summarize Procurement (Purchase Orders / Purchases) and Accounts Payable balances.
     */
    public function procurementAndApSummary(?string $from = null, ?string $to = null): array
    {
        [$fromAt, $toAt] = $this->normalizeDateRange($from, $to);

        $poCount = 0;
        $poTotal = 0.0;

        if (Schema::hasTable('purchase_orders')) {
            $dateCol = Schema::hasColumn('purchase_orders', 'order_date') ? 'order_date' : 'created_at';
            $totalCol = Schema::hasColumn('purchase_orders', 'total_amount')
                ? 'total_amount'
                : (Schema::hasColumn('purchase_orders', 'total') ? 'total' : null);

            $poQuery = DB::table('purchase_orders')
                ->where('status', '!=', 'cancelled');

            if ($fromAt) {
                $poQuery->where($dateCol, '>=', $dateCol === 'order_date' ? $fromAt->toDateString() : $fromAt);
            }
            if ($toAt) {
                $poQuery->where($dateCol, '<=', $dateCol === 'order_date' ? $toAt->toDateString() : $toAt);
            }

            $poCount = (int) (clone $poQuery)->count();
            $poTotal = $totalCol ? round((float) (clone $poQuery)->sum($totalCol), 2) : 0.0;
        } else {
            $purchaseQuery = Purchase::query()
                ->where('status', '!=', PurchaseStatus::CANCELLED->value);

            if ($fromAt) {
                $purchaseQuery->where('purchase_date', '>=', $fromAt->toDateString());
            }
            if ($toAt) {
                $purchaseQuery->where('purchase_date', '<=', $toAt->toDateString());
            }

            $poCount = (int) (clone $purchaseQuery)->count();
            $poTotal = round((float) (clone $purchaseQuery)->sum('total'), 2);
        }

        $apQuery = AccountsPayable::query();
        if ($fromAt) {
            $apQuery->where('created_at', '>=', $fromAt);
        }
        if ($toAt) {
            $apQuery->where('created_at', '<=', $toAt);
        }

        $paymentsQuery = AccountsPayablePayment::query();
        if ($fromAt) {
            $paymentsQuery->where('payment_date', '>=', $fromAt->toDateString());
        }
        if ($toAt) {
            $paymentsQuery->where('payment_date', '<=', $toAt->toDateString());
        }

        $apCount = (int) (clone $apQuery)->count();
        $apTotalAmount = round((float) (clone $apQuery)->sum('total_amount'), 2);
        $apTotalDue = round((float) (clone $apQuery)->sum('balance_due'), 2);
        $apPaidTotal = round((float) (clone $paymentsQuery)->sum('amount'), 2);

        return [
            'from' => $from,
            'to' => $to,
            'po_count' => $poCount,
            'po_total' => $poTotal,
            'ap_count' => $apCount,
            'ap_total_amount' => $apTotalAmount,
            'ap_total_due' => $apTotalDue,
            'ap_paid_total' => $apPaidTotal,
            'ap_unpaid_count' => (int) (clone $apQuery)->where('status', AccountsPayable::STATUS_UNPAID)->count(),
            'ap_partial_count' => (int) (clone $apQuery)->where('status', AccountsPayable::STATUS_PARTIAL)->count(),
            'ap_paid_count' => (int) (clone $apQuery)->where('status', AccountsPayable::STATUS_PAID)->count(),
        ];
    }

    /**
     * Summarize team task SLA productivity (completed vs overdue).
     */
    public function teamSlaProductivity(?string $from = null, ?string $to = null): array
    {
        [$fromAt, $toAt] = $this->normalizeDateRange($from, $to);

        $tasksQuery = OrderTask::query();
        if ($fromAt) {
            $tasksQuery->where('created_at', '>=', $fromAt);
        }
        if ($toAt) {
            $tasksQuery->where('created_at', '<=', $toAt);
        }

        $totalTasks = (int) (clone $tasksQuery)->count();
        $completedTasks = (int) (clone $tasksQuery)
            ->where('status', OrderTask::STATUS_COMPLETED)
            ->count();

        $overdueTasks = (int) (clone $tasksQuery)
            ->whereNotIn('status', [OrderTask::STATUS_COMPLETED, OrderTask::STATUS_CANCELLED])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $inProgressTasks = (int) (clone $tasksQuery)
            ->whereIn('status', [
                OrderTask::STATUS_PENDING,
                OrderTask::STATUS_ASSIGNED,
                OrderTask::STATUS_IN_PROGRESS,
                OrderTask::STATUS_REVIEW,
                OrderTask::STATUS_REVISION,
            ])
            ->count();

        $onTimeCompleted = (int) (clone $tasksQuery)
            ->where('status', OrderTask::STATUS_COMPLETED)
            ->where(function ($q): void {
                $q->whereNull('due_at')
                    ->orWhereColumn('completed_at', '<=', 'due_at');
            })
            ->count();

        $now = now();
        $tasks = (clone $tasksQuery)->get(['department', 'status', 'due_at', 'completed_at']);
        $byDepartment = $tasks->groupBy('department')->map(function ($deptTasks, $dept) use ($now): array {
            $total = $deptTasks->count();
            $completed = $deptTasks->where('status', OrderTask::STATUS_COMPLETED)->count();
            $overdue = $deptTasks->filter(function (OrderTask $task) use ($now): bool {
                return ! in_array($task->status, [OrderTask::STATUS_COMPLETED, OrderTask::STATUS_CANCELLED], true)
                    && $task->due_at !== null
                    && $now->gt($task->due_at);
            })->count();

            return [
                'department' => (string) $dept,
                'total' => $total,
                'completed' => $completed,
                'overdue' => $overdue,
            ];
        })->values()->all();

        return [
            'from' => $from,
            'to' => $to,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'in_progress_tasks' => $inProgressTasks,
            'on_time_completed' => $onTimeCompleted,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0.0,
            'by_department' => $byDepartment,
        ];
    }

    /**
     * Stream a CSV export for the given report type.
     */
    public function exportCsv(string $reportType, ?string $from = null, ?string $to = null): StreamedResponse
    {
        $reportType = strtolower(trim($reportType));
        if (! in_array($reportType, ['sales', 'stock', 'procurement', 'sla'], true)) {
            $reportType = 'sales';
        }

        $rows = match ($reportType) {
            'sales' => $this->buildSalesCsvRows($from, $to),
            'stock' => $this->buildStockCsvRows($from, $to),
            'procurement' => $this->buildProcurementCsvRows($from, $to),
            'sla' => $this->buildSlaCsvRows($from, $to),
        };

        $filename = "report-{$reportType}.csv";

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function buildSalesCsvRows(?string $from, ?string $to): array
    {
        $data = $this->salesComparison($from, $to);

        return [
            ['Channel', 'Transactions Count', 'Total Amount', 'From', 'To'],
            ['POS Sales', $data['pos_sales_count'], number_format($data['pos_sales_total'], 2, '.', ''), $from ?? '-', $to ?? '-'],
            ['Customer Portal Orders', $data['portal_orders_count'], number_format($data['portal_orders_total'], 2, '.', ''), $from ?? '-', $to ?? '-'],
            ['Combined', $data['combined_count'], number_format($data['combined_total'], 2, '.', ''), $from ?? '-', $to ?? '-'],
        ];
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function buildStockCsvRows(?string $from, ?string $to): array
    {
        $data = $this->stockValuationAndMovements($from, $to);

        $rows = [
            ['Metric / Movement Type', 'Count / SKUs', 'Quantity / Value'],
            ['Total SKUs', $data['total_skus'], $data['total_on_hand']],
            ['Stock Valuation', $data['total_on_hand'], number_format($data['stock_value'], 2, '.', '')],
        ];

        foreach ($data['movements_by_type'] as $movement) {
            $rows[] = [
                'Movement: ' . $movement['type'],
                $movement['count'],
                $movement['total_qty'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function buildProcurementCsvRows(?string $from, ?string $to): array
    {
        $data = $this->procurementAndApSummary($from, $to);

        return [
            ['Metric', 'Count', 'Amount'],
            ['Purchase Orders', $data['po_count'], number_format($data['po_total'], 2, '.', '')],
            ['Accounts Payable Total', $data['ap_count'], number_format($data['ap_total_amount'], 2, '.', '')],
            ['Accounts Payable Paid', $data['ap_paid_count'], number_format($data['ap_paid_total'], 2, '.', '')],
            ['Accounts Payable Balance Due', $data['ap_unpaid_count'] + $data['ap_partial_count'], number_format($data['ap_total_due'], 2, '.', '')],
        ];
    }

    /**
     * @return array<int, array<int, scalar|null>>
     */
    private function buildSlaCsvRows(?string $from, ?string $to): array
    {
        $data = $this->teamSlaProductivity($from, $to);

        $rows = [
            ['Department / Scope', 'Total Tasks', 'Completed', 'Overdue', 'Completion Rate (%)'],
            ['All Departments', $data['total_tasks'], $data['completed_tasks'], $data['overdue_tasks'], $data['completion_rate']],
        ];

        foreach ($data['by_department'] as $dept) {
            $rate = $dept['total'] > 0 ? round(($dept['completed'] / $dept['total']) * 100, 1) : 0.0;
            $rows[] = [
                $dept['department'],
                $dept['total'],
                $dept['completed'],
                $dept['overdue'],
                $rate,
            ];
        }

        return $rows;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function normalizeDateRange(?string $from, ?string $to): array
    {
        $fromAt = $from ? Carbon::parse($from)->startOfDay() : null;
        $toAt = $to ? Carbon::parse($to)->endOfDay() : null;

        return [$fromAt, $toAt];
    }
}
