<?php

use App\Http\Controllers\AccountsPayableController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\Api\BarcodeLookupController;
use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WorkManagementController;
use Illuminate\Support\Facades\Route;

Route::post('/api/v1/webhooks/payment/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:payment-webhook')
    ->name('payments.webhook');
Route::post('/api/v1/callback/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:payment-webhook')
    ->name('payments.callback');

// Public Customer Portal & Order Tracking
Route::prefix('shop')->name('shop.')->group(function () {
    Route::get('/', [ShopController::class, 'index'])->name('index');
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->middleware('guest')->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware(['guest', 'throttle:register'])->name('register.store');
    Route::post('/cart/{product}', [ShopController::class, 'addToCart'])->name('cart.add');
    Route::patch('/cart/{product}', [ShopController::class, 'updateCart'])->name('cart.update');
    Route::delete('/cart/{product}', [ShopController::class, 'removeFromCart'])->name('cart.remove');

    Route::middleware('auth')->group(function () {
        Route::get('/checkout', [ShopController::class, 'checkout'])->name('checkout');
        Route::post('/checkout', [ShopController::class, 'placeOrder'])->middleware('throttle:checkout')->name('place-order');
    });
});

Route::get('/track/{token}', [PublicTrackingController::class, 'show'])
    ->middleware('throttle:public-tracking')
    ->name('tracking.show');
Route::get('/api/v1/track/{token}', [PublicTrackingController::class, 'api'])
    ->middleware('throttle:public-tracking')
    ->name('tracking.api');

Route::middleware(['auth', 'verified'])->group(function () {
    // =========================================================================
    // Dashboard & Profile
    // =========================================================================
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
    Route::view('profile', 'profile.index')->name('profile.index');

    // =========================================================================
    // Master Data
    // =========================================================================
    Route::prefix('master')->middleware('permission:master.view')->group(function () {
        Route::view('customers', 'customers.index')->name('customers.index');
        Route::view('suppliers', 'suppliers.index')->name('suppliers.index');
        Route::view('categories', 'categories.index')->name('categories.index');
        Route::view('units', 'units.index')->name('units.index');
        Route::view('products', 'products.index')->name('products.index');
    });

    // =========================================================================
    // Transactions
    // =========================================================================

    // Purchases
    Route::middleware('permission:purchases.view')->group(function () {
        Route::resource('purchases', PurchaseController::class);
        Route::prefix('purchases/{purchase}')->name('purchases.')->controller(PurchaseController::class)->group(function () {
            Route::patch('ordered', 'markOrdered')->name('mark-ordered');
            Route::patch('received', 'markReceived')->name('mark-received');
            Route::patch('paid', 'markPaid')->name('mark-paid');
            Route::patch('cancel', 'cancel')->name('cancel');
            Route::patch('restore-draft', 'restoreToDraft')->name('restore-draft');
        });
    });

    // POS & Sales
    Route::middleware('permission:pos.access')->group(function () {
        Route::get('sales/create', [SalesController::class, 'create'])->name('sales.create');
        Route::post('sales', [SalesController::class, 'store'])->name('sales.store');

        Route::get('pos/shifts', [CashShiftController::class, 'index'])->name('pos.shifts.index');
        Route::post('pos/shifts/open', [CashShiftController::class, 'open'])->name('pos.shifts.open');
        Route::patch('pos/shifts/{shift}/close', [CashShiftController::class, 'close'])->name('pos.shifts.close');
    });

    // Inventory Ledger & Stock Opname
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::middleware('permission:inventory.view')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('movements', [InventoryController::class, 'movements'])->name('movements');
            Route::get('opnames', [InventoryController::class, 'opnames'])->name('opnames');
        });
        Route::post('adjust', [InventoryController::class, 'adjust'])
            ->middleware('permission:inventory.adjust')
            ->name('adjust');
        Route::post('opnames', [InventoryController::class, 'storeOpname'])
            ->middleware('permission:inventory.manage')
            ->name('opnames.store');
        Route::patch('opnames/{opname}/approve', [InventoryController::class, 'approveOpname'])
            ->middleware('permission:inventory.adjust')
            ->name('opnames.approve');
    });

    Route::middleware('permission:sales.view')->group(function () {
        Route::get('sales', [SalesController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [SalesController::class, 'show'])->name('sales.show');
        Route::delete('sales/{sale}', [SalesController::class, 'destroy'])->name('sales.destroy');
        Route::prefix('sales/{sale}')->name('sales.')->controller(SalesController::class)->group(function () {
            Route::get('print', 'print')->name('print');
            Route::get('status', 'checkStatus')->name('status');
            Route::patch('complete', 'complete')->name('complete');
            Route::patch('restore', 'restore')->name('restore');
        });
    });

    // =========================================================================
    // Customer Self-Service Orders & Addresses
    // =========================================================================
    Route::prefix('my-orders')->name('my-orders.')->group(function () {
        Route::get('/', [CustomerOrderController::class, 'index'])->name('index');
        Route::post('/addresses', [CustomerOrderController::class, 'storeAddress'])->name('addresses.store');
        Route::get('/{order}', [CustomerOrderController::class, 'show'])->name('show');
        Route::post('/{order}/proof', [CustomerOrderController::class, 'uploadProof'])->name('upload-proof');
        Route::post('/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('cancel');
    });

    // =========================================================================
    // Staff Order Management, Work Management & Shipping
    // =========================================================================
    Route::prefix('orders')->name('orders.')->middleware(['permission:dashboard.view', 'permission:orders.view'])->group(function () {
        Route::get('/', [AdminOrderController::class, 'index'])->name('index');
        Route::get('/{order}', [AdminOrderController::class, 'show'])->name('show');
        Route::post('/{order}/verify-payment', [AdminOrderController::class, 'verifyPayment'])
            ->middleware('permission:orders.manage|finance.verify_payment')
            ->name('verify-payment');
        Route::post('/{order}/cancel', [AdminOrderController::class, 'cancel'])
            ->middleware('permission:orders.manage')
            ->name('cancel');
    });

    Route::prefix('work')->name('work.')->middleware('permission:work.view')->group(function () {
        Route::get('/board', [WorkManagementController::class, 'board'])->name('board');
        Route::get('/my-tasks', [WorkManagementController::class, 'myTasks'])->name('my-tasks');
        Route::get('/tasks', [WorkManagementController::class, 'allTasks'])->name('tasks');
        Route::get('/workflows', [WorkManagementController::class, 'workflows'])->name('workflows');
        Route::post('/workflows/steps', [WorkManagementController::class, 'storeWorkflowStep'])
            ->middleware('permission:work.manage|settings.manage')
            ->name('workflows.step.store');

        Route::patch('/tasks/{task}/start', [WorkManagementController::class, 'start'])
            ->name('start');
        Route::patch('/tasks/{task}/complete', [WorkManagementController::class, 'complete'])
            ->name('complete');
        Route::patch('/tasks/{task}/revision', [WorkManagementController::class, 'revision'])
            ->middleware('permission:work.qc')
            ->name('revision');
        Route::patch('/tasks/{task}/reassign', [WorkManagementController::class, 'reassign'])
            ->middleware('permission:work.assign')
            ->name('reassign');
        Route::post('/tasks/{task}/comment', [WorkManagementController::class, 'comment'])->name('comment');
    });

    Route::prefix('shipping')->name('shipping.')->group(function () {
        Route::get('/', [ShippingController::class, 'index'])
            ->middleware('permission:shipping.view')
            ->name('index');
        Route::post('/orders/{order}/dispatch', [ShippingController::class, 'dispatch'])
            ->middleware('permission:shipping.manage')
            ->name('dispatch');
        Route::post('/shipments/{shipment}/confirm-delivery', [ShippingController::class, 'confirmDelivery'])
            ->name('confirm-delivery');
    });

    // =========================================================================
    // Procurement (PR -> PO Maker-Checker -> GRN -> 3-Way Match -> Return)
    // =========================================================================
    Route::prefix('procurement')->name('procurement.')->middleware('permission:purchases.view')->group(function () {
        Route::get('/', [ProcurementController::class, 'index'])->name('index');

        Route::get('/requests', [ProcurementController::class, 'requests'])->name('requests');
        Route::post('/requests', [ProcurementController::class, 'storeRequest'])
            ->middleware('permission:purchases.create')
            ->name('requests.store');
        Route::post('/requests/{purchaseRequest}/approve', [ProcurementController::class, 'approveRequest'])
            ->middleware('permission:purchases.approve')
            ->name('requests.approve');
        Route::post('/requests/{purchaseRequest}/reject', [ProcurementController::class, 'rejectRequest'])
            ->middleware('permission:purchases.approve')
            ->name('requests.reject');

        Route::get('/orders', [ProcurementController::class, 'orders'])->name('orders');
        Route::post('/orders', [ProcurementController::class, 'storeOrder'])
            ->middleware('permission:purchases.create')
            ->name('orders.store');
        Route::post('/orders/{purchaseOrder}/submit', [ProcurementController::class, 'submitOrder'])
            ->middleware('permission:purchases.create')
            ->name('orders.submit');
        Route::post('/orders/{purchaseOrder}/approve', [ProcurementController::class, 'approveOrder'])
            ->middleware('permission:purchases.approve')
            ->name('orders.approve');
        Route::post('/orders/{purchaseOrder}/revise', [ProcurementController::class, 'reviseOrder'])
            ->middleware('permission:purchases.create|purchases.approve')
            ->name('orders.revise');

        Route::get('/receipts', [ProcurementController::class, 'receipts'])->name('receipts');
        Route::post('/receipts', [ProcurementController::class, 'storeReceipt'])
            ->middleware('permission:purchases.receive')
            ->name('receipts.store');

        Route::get('/invoices', [ProcurementController::class, 'invoices'])->name('invoices');
        Route::post('/invoices', [ProcurementController::class, 'storeInvoice'])
            ->middleware('permission:finance.manage|purchases.create')
            ->name('invoices.store');

        Route::get('/returns', [ProcurementController::class, 'returns'])->name('returns');
        Route::post('/returns', [ProcurementController::class, 'storeReturn'])
            ->middleware('permission:purchases.receive|purchases.create')
            ->name('returns.store');
    });

    // =========================================================================
    // Finance, Accounts Payable & Refunds
    // =========================================================================
    Route::prefix('finance')->name('finance.')->middleware('permission:finance.view')->group(function () {
        Route::view('categories', 'finance-categories.index')->name('categories.index');
        Route::view('transactions', 'finance-transactions.index')->name('transactions.index');
        Route::get('transactions/print/{printId}', [FinanceReportController::class, 'print'])->name('transactions.print');

        Route::get('ap', [AccountsPayableController::class, 'index'])->name('ap.index');
        Route::post('ap/{accountsPayable}/pay', [AccountsPayableController::class, 'pay'])
            ->middleware('permission:finance.manage')
            ->name('ap.pay');

        Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::post('refunds', [RefundController::class, 'store'])
            ->middleware('permission:finance.manage')
            ->name('refunds.store');
        Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])
            ->middleware('permission:finance.manage')
            ->name('refunds.approve');
        Route::post('refunds/{refund}/process', [RefundController::class, 'process'])
            ->middleware('permission:finance.manage')
            ->name('refunds.process');
        Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])
            ->middleware('permission:finance.manage')
            ->name('refunds.reject');
    });

    // =========================================================================
    // Reports & Private File Downloads
    // =========================================================================
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export/{type?}', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::prefix('files')->name('files.')->group(function () {
        Route::get('payment-proof/{payment}', [PrivateFileController::class, 'orderPaymentProof'])->name('payment-proof');
        Route::get('task-attachment/{attachment}', [PrivateFileController::class, 'taskAttachment'])->name('task-attachment');
        Route::get('shipment-pod/{shipment}', [PrivateFileController::class, 'shipmentPod'])->name('shipment-pod');
    });

    // =========================================================================
    // Settings, Users & Audit Logs
    // =========================================================================
    Route::view('users', 'users.index')->middleware('permission:users.view')->name('users.index');
    Route::view('settings', 'settings.index')->middleware('permission:settings.view')->name('settings.index');
    Route::view('audit-logs', 'audit-logs.index')->middleware('permission:audit.view')->name('audit.index');

    // =========================================================================
    // Internal APIs (AJAX)
    // =========================================================================
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('products/barcode/{code}', [BarcodeLookupController::class, 'lookup'])->name('products.barcode');
        Route::post('products', [\App\Http\Controllers\Api\ProductController::class, 'search'])->name('products.search');
        Route::post('suppliers', [\App\Http\Controllers\Api\SupplierController::class, 'search'])->name('suppliers.search');
        Route::post('customers', [\App\Http\Controllers\Api\CustomerController::class, 'search'])->name('customers.search');
        Route::post('customers/store', [\App\Http\Controllers\Api\CustomerController::class, 'store'])->name('customers.store');
        Route::post('categories', [\App\Http\Controllers\Api\CategoryController::class, 'search'])->name('categories.search');
        Route::post('units', [\App\Http\Controllers\Api\UnitController::class, 'search'])->name('units.search');
        Route::post('users', [\App\Http\Controllers\Api\UserController::class, 'search'])->name('users.search');
        Route::post('finance-categories', [\App\Http\Controllers\Api\FinanceCategoryController::class, 'search'])->name('finance-categories.search');
    });
});

require __DIR__.'/auth.php';
