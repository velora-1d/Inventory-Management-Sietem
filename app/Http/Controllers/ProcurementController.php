<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Warehouse;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function index(): View
    {
        $summary = [
            'pending_requests' => PurchaseRequest::query()->where('status', PurchaseRequest::STATUS_PENDING_APPROVAL)->count(),
            'approved_requests' => PurchaseRequest::query()->where('status', PurchaseRequest::STATUS_APPROVED)->count(),
            'pending_orders' => PurchaseOrder::query()->where('status', PurchaseOrder::STATUS_PENDING_APPROVAL)->count(),
            'open_orders' => PurchaseOrder::query()->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])->count(),
            'goods_receipts' => GoodsReceipt::query()->count(),
            'matched_invoices' => SupplierInvoice::query()->where('match_status', SupplierInvoice::MATCH_MATCHED)->count(),
            'discrepancy_invoices' => SupplierInvoice::query()->where('match_status', SupplierInvoice::MATCH_DISCREPANCY)->count(),
            'purchase_returns' => PurchaseReturn::query()->count(),
        ];

        $recentRequests = PurchaseRequest::query()
            ->with(['requester', 'items'])
            ->latest('id')
            ->limit(5)
            ->get();

        $recentOrders = PurchaseOrder::query()
            ->with(['supplier', 'creator', 'items'])
            ->latest('id')
            ->limit(5)
            ->get();

        $recentInvoices = SupplierInvoice::query()
            ->with(['supplier', 'purchaseOrder'])
            ->latest('id')
            ->limit(5)
            ->get();

        return view('procurement.index', compact(
            'summary',
            'recentRequests',
            'recentOrders',
            'recentInvoices'
        ));
    }

    public function requests(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));

        $query = PurchaseRequest::query()
            ->with(['requester', 'approver', 'items.product', 'approvals.actor']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(pr_number) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(COALESCE(notes, \'\')) LIKE ?', [$pattern]);
            });
        }

        $requests = $query->latest('id')->paginate(15)->withQueryString();
        $products = Product::query()->orderBy('name')->get(['id', 'sku', 'name', 'purchase_price']);

        return view('procurement.requests', compact('requests', 'products', 'search', 'status'));
    }

    public function storeRequest(Request $request, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $pr = $service->createPurchaseRequest(
                requester: Auth::user(),
                items: $validated['items'],
                notes: $validated['notes'] ?? null
            );

            return $this->redirectToRoute('procurement.requests', '/procurement/requests')
                ->with('success', "Purchase Request {$pr->pr_number} created and queued for approval.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function approveRequest(Request $request, PurchaseRequest $purchaseRequest, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->approvePurchaseRequest($purchaseRequest, Auth::user(), $validated['notes'] ?? null);

            return $this->redirectToRoute('procurement.requests', '/procurement/requests')
                ->with('success', "Purchase Request {$purchaseRequest->pr_number} approved.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectRequest(Request $request, PurchaseRequest $purchaseRequest, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $service->rejectPurchaseRequest($purchaseRequest, Auth::user(), $validated['reason']);

            return $this->redirectToRoute('procurement.requests', '/procurement/requests')
                ->with('success', "Purchase Request {$purchaseRequest->pr_number} rejected.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function orders(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;

        $query = PurchaseOrder::query()
            ->with(['supplier', 'purchaseRequest', 'creator', 'approver', 'items.product', 'approvals.actor']);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($supplierId !== null) {
            $query->where('supplier_id', $supplierId);
        }

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(po_number) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(COALESCE(notes, \'\')) LIKE ?', [$pattern]);
            });
        }

        $orders = $query->latest('id')->paginate(15)->withQueryString();
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name']);
        $products = Product::query()->orderBy('name')->get(['id', 'sku', 'name', 'purchase_price']);
        $approvedRequests = PurchaseRequest::query()
            ->with('items.product')
            ->where('status', PurchaseRequest::STATUS_APPROVED)
            ->latest('id')
            ->get();

        return view('procurement.orders', compact(
            'orders',
            'suppliers',
            'products',
            'approvedRequests',
            'search',
            'status',
            'supplierId'
        ));
    }

    public function storeOrder(Request $request, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_request_id' => ['nullable', 'integer', 'exists:purchase_requests,id'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $po = $service->createPurchaseOrder(
                creator: Auth::user(),
                supplierId: (int) $validated['supplier_id'],
                items: $validated['items'],
                purchaseRequestId: ! empty($validated['purchase_request_id']) ? (int) $validated['purchase_request_id'] : null,
                expectedDate: $validated['expected_date'] ?? null,
                notes: $validated['notes'] ?? null
            );

            return $this->redirectToRoute('procurement.orders', '/procurement/orders')
                ->with('success', "Purchase Order {$po->po_number} created as draft.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function submitOrder(PurchaseOrder $purchaseOrder, ProcurementService $service): RedirectResponse
    {
        try {
            $service->submitPurchaseOrder($purchaseOrder, Auth::user());

            return $this->redirectToRoute('procurement.orders', '/procurement/orders')
                ->with('success', "Purchase Order {$purchaseOrder->po_number} submitted for approval.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveOrder(Request $request, PurchaseOrder $purchaseOrder, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->approvePurchaseOrder($purchaseOrder, Auth::user(), $validated['notes'] ?? null);

            return $this->redirectToRoute('procurement.orders', '/procurement/orders')
                ->with('success', "Purchase Order {$purchaseOrder->po_number} approved and incoming stock updated.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reviseOrder(Request $request, PurchaseOrder $purchaseOrder, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $revised = $service->revisePurchaseOrder(
                po: $purchaseOrder,
                actor: Auth::user(),
                items: $validated['items'],
                reason: $validated['reason']
            );

            return $this->redirectToRoute('procurement.orders', '/procurement/orders')
                ->with('success', "Purchase Order {$revised->po_number} revised (Rev #{$revised->revision_number}) and returned for approval.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function receipts(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $qcStatus = trim((string) $request->input('qc_status', ''));

        $query = GoodsReceipt::query()
            ->with(['purchaseOrder.supplier', 'warehouse', 'receiver', 'items.product']);

        if ($qcStatus !== '') {
            $query->where('qc_status', $qcStatus);
        }

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(grn_number) LIKE ?', [$pattern])
                    ->orWhereHas('purchaseOrder', function ($poQuery) use ($pattern): void {
                        $poQuery->whereRaw('LOWER(po_number) LIKE ?', [$pattern]);
                    });
            });
        }

        $receipts = $query->latest('id')->paginate(15)->withQueryString();
        $receivableOrders = PurchaseOrder::query()
            ->with(['supplier', 'items.product'])
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->latest('id')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('procurement.receipts', compact(
            'receipts',
            'receivableOrders',
            'warehouses',
            'search',
            'qcStatus'
        ));
    }

    public function storeReceipt(Request $request, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'qc_status' => ['required', 'in:passed,partial,failed'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:0'],
            'items.*.quantity_accepted' => ['nullable', 'integer', 'min:0'],
            'items.*.quantity_rejected' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $po = PurchaseOrder::query()->findOrFail((int) $validated['purchase_order_id']);
            $grn = $service->receiveGoods(
                po: $po,
                receiver: Auth::user(),
                receivedItems: $validated['items'],
                warehouseId: ! empty($validated['warehouse_id']) ? (int) $validated['warehouse_id'] : null,
                qcStatus: $validated['qc_status'],
                notes: $validated['notes'] ?? null
            );

            return $this->redirectToRoute('procurement.receipts', '/procurement/receipts')
                ->with('success', "Goods Receipt {$grn->grn_number} recorded and inventory stock updated.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function invoices(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $matchStatus = trim((string) $request->input('match_status', ''));

        $query = SupplierInvoice::query()
            ->with(['supplier', 'purchaseOrder', 'goodsReceipt', 'verifier']);

        if ($matchStatus !== '') {
            $query->where('match_status', $matchStatus);
        }

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(invoice_number) LIKE ?', [$pattern])
                    ->orWhereHas('purchaseOrder', function ($poQuery) use ($pattern): void {
                        $poQuery->whereRaw('LOWER(po_number) LIKE ?', [$pattern]);
                    });
            });
        }

        $invoices = $query->latest('id')->paginate(15)->withQueryString();
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'goodsReceipts'])
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                PurchaseOrder::STATUS_FULLY_RECEIVED,
            ])
            ->latest('id')
            ->get();

        return view('procurement.invoices', compact(
            'invoices',
            'purchaseOrders',
            'search',
            'matchStatus'
        ));
    }

    public function storeInvoice(Request $request, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'goods_receipt_id' => ['nullable', 'integer', 'exists:goods_receipts,id'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'match_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $po = PurchaseOrder::query()->findOrFail((int) $validated['purchase_order_id']);
            $invoice = $service->recordSupplierInvoice($po, Auth::user(), $validated);

            $statusMsg = $invoice->match_status === SupplierInvoice::MATCH_MATCHED
                ? "Invoice {$invoice->invoice_number} verified (3-way match passed)."
                : "Invoice {$invoice->invoice_number} flagged with 3-way match discrepancy.";

            return $this->redirectToRoute('procurement.invoices', '/procurement/invoices')
                ->with($invoice->match_status === SupplierInvoice::MATCH_MATCHED ? 'success' : 'error', $statusMsg);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function returns(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $query = PurchaseReturn::query()
            ->with(['supplier', 'purchaseOrder', 'goodsReceipt', 'creator', 'items.product']);

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw('LOWER(return_number) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(reason) LIKE ?', [$pattern]);
            });
        }

        $returns = $query->latest('id')->paginate(15)->withQueryString();
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'items.product', 'goodsReceipts'])
            ->whereIn('status', [
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                PurchaseOrder::STATUS_FULLY_RECEIVED,
            ])
            ->latest('id')
            ->get();

        return view('procurement.returns', compact('returns', 'purchaseOrders', 'search'));
    }

    public function storeReturn(Request $request, ProcurementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'goods_receipt_id' => ['nullable', 'integer', 'exists:goods_receipts,id'],
            'reason' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $po = PurchaseOrder::query()->findOrFail((int) $validated['purchase_order_id']);
            $return = $service->createPurchaseReturn(
                po: $po,
                actor: Auth::user(),
                items: $validated['items'],
                reason: $validated['reason'],
                goodsReceiptId: ! empty($validated['goods_receipt_id']) ? (int) $validated['goods_receipt_id'] : null
            );

            return $this->redirectToRoute('procurement.returns', '/procurement/returns')
                ->with('success', "Purchase Return {$return->return_number} completed and inventory returned out.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function redirectToRoute(string $routeName, string $fallbackPath): RedirectResponse
    {
        $url = Route::has($routeName) ? route($routeName) : url($fallbackPath);

        return redirect()->to($url);
    }
}
