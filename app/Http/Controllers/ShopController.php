<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Sale;
use App\Payments\PaymentGatewayManager;
use App\Services\CustomerOrderService;
use App\Services\InventoryLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShopController extends Controller
{
    private const CART_SESSION_KEY = 'shop_cart';

    public function index(Request $request, InventoryLedgerService $ledger): View
    {
        $search = trim((string) $request->input('search', ''));
        $selectedCategoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with(['category', 'unit', 'stockBalances'])
            ->orderBy('name');

        if ($search !== '') {
            $pattern = '%' . mb_strtolower($search) . '%';
            $productsQuery->where(function ($query) use ($pattern): void {
                $query->whereRaw('LOWER(name) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$pattern]);
            });
        }

        if ($selectedCategoryId) {
            $productsQuery->where('category_id', $selectedCategoryId);
        }

        $products = $productsQuery->paginate(12)->withQueryString();
        $products->getCollection()->transform(function (Product $product) use ($ledger): Product {
            $product->setAttribute('available_stock', $ledger->availableStock($product->id));

            return $product;
        });

        $categories = Category::query()->orderBy('name')->get();
        [$cartItems, $cartSubtotal, $cartCount] = $this->buildCartSummary($ledger);

        return view('shop.index', compact(
            'products',
            'categories',
            'cartItems',
            'cartSubtotal',
            'cartCount',
            'search',
            'selectedCategoryId'
        ));
    }

    public function addToCart(Request $request, Product $product, InventoryLedgerService $ledger): RedirectResponse
    {
        if (! $product->is_active) {
            return back()->with('error', 'Produk tidak tersedia untuk dipesan.');
        }

        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $qtyToAdd = (int) ($validated['quantity'] ?? 1);
        $cart = $this->getCart();
        $newQty = ($cart[$product->id] ?? 0) + $qtyToAdd;
        $available = $ledger->availableStock($product->id);

        if ($newQty > $available) {
            return back()->with('error', "Stok tersedia untuk {$product->name} hanya {$available} unit.");
        }

        $cart[$product->id] = $newQty;
        session([self::CART_SESSION_KEY => $cart]);

        return back()->with('success', "{$product->name} ditambahkan ke keranjang.");
    }

    public function updateCart(Request $request, Product $product, InventoryLedgerService $ledger): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $qty = (int) $validated['quantity'];
        $cart = $this->getCart();

        if ($qty <= 0) {
            unset($cart[$product->id]);
            session([self::CART_SESSION_KEY => $cart]);

            return back()->with('success', "{$product->name} dihapus dari keranjang.");
        }

        $available = $ledger->availableStock($product->id);
        if ($qty > $available) {
            return back()->with('error', "Stok tersedia untuk {$product->name} hanya {$available} unit.");
        }

        $cart[$product->id] = $qty;
        session([self::CART_SESSION_KEY => $cart]);

        return back()->with('success', "Jumlah {$product->name} diperbarui.");
    }

    public function removeFromCart(Product $product): RedirectResponse
    {
        $cart = $this->getCart();
        unset($cart[$product->id]);
        session([self::CART_SESSION_KEY => $cart]);

        return back()->with('success', "{$product->name} dihapus dari keranjang.");
    }

    public function checkout(InventoryLedgerService $ledger, PaymentGatewayManager $paymentManager): View|RedirectResponse
    {
        [$cartItems, $cartSubtotal, $cartCount] = $this->buildCartSummary($ledger);

        if (empty($cartItems)) {
            return redirect()->route('shop.index')->with('error', 'Keranjang belanja masih kosong.');
        }

        $addresses = Auth::check()
            ? CustomerAddress::query()
                ->where('user_id', Auth::id())
                ->orderByDesc('is_default')
                ->latest('id')
                ->get()
            : collect();

        $gateways = $paymentManager->availableGateways();
        $activeGateway = $paymentManager->activeDriverKey();

        return view('shop.checkout', compact(
            'cartItems',
            'cartSubtotal',
            'cartCount',
            'addresses',
            'gateways',
            'activeGateway'
        ));
    }

    public function placeOrder(Request $request, CustomerOrderService $orderService, PaymentGatewayManager $paymentManager): RedirectResponse
    {
        $rules = [
            'delivery_type' => ['required', 'in:pickup,delivery'],
            'customer_address_id' => ['nullable', 'integer'],
            'payment_method' => ['required', 'string', 'max:40'],
            'gateway' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];

        if ($request->input('delivery_type') === 'delivery' && ! $request->filled('customer_address_id')) {
            $rules['recipient_name'] = ['required', 'string', 'max:150'];
            $rules['phone'] = ['required', 'string', 'max:50'];
            $rules['address_line'] = ['required', 'string', 'max:1000'];
            $rules['city'] = ['required', 'string', 'max:100'];
            $rules['province'] = ['nullable', 'string', 'max:100'];
            $rules['postal_code'] = ['nullable', 'string', 'max:20'];
            $rules['address_label'] = ['nullable', 'string', 'max:100'];
        }

        $validated = $request->validate($rules);

        $items = $validated['items'] ?? [];
        if (empty($items)) {
            $cart = $this->getCart();
            foreach ($cart as $productId => $qty) {
                $items[] = [
                    'product_id' => (int) $productId,
                    'quantity' => (int) $qty,
                ];
            }
        }

        if (empty($items)) {
            return back()->withInput()->with('error', 'Keranjang belanja masih kosong.');
        }

        try {
            $order = $orderService->placeOrder($request->user(), $items, $validated);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        session()->forget(self::CART_SESSION_KEY);

        $paymentMethod = PaymentMethod::tryFrom((string) ($validated['payment_method'] ?? ''));
        $requestedGateway = $validated['gateway'] ?? null;

        if ($paymentMethod && $paymentManager->shouldUseGateway($paymentMethod, $requestedGateway)) {
            try {
                $gateway = $paymentManager->driver($requestedGateway);
                $pseudoSale = new Sale([
                    'invoice_number' => $order->order_number,
                    'total' => $order->total,
                    'payment_method' => $paymentMethod,
                ]);
                $checkout = $gateway->createCheckout(
                    $pseudoSale,
                    route('my-orders.show', $order),
                    ['method' => $paymentMethod->value]
                );

                $order->payments()->latest('id')->first()?->update([
                    'gateway' => $gateway->key(),
                    'reference' => $checkout->reference,
                    'notes' => $checkout->checkoutUrl ?: null,
                ]);

                if ($checkout->requiresRedirect && !empty($checkout->checkoutUrl)) {
                    return redirect()->away($checkout->checkoutUrl);
                }
            } catch (\Throwable $e) {
                return redirect()
                    ->route('my-orders.show', $order)
                    ->with('error', 'Pesanan dibuat, tetapi gagal membuka halaman pembayaran: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('my-orders.show', $order)
            ->with('success', "Pesanan #{$order->order_number} berhasil dibuat.");
    }

    /**
     * @return array<int, int>
     */
    private function getCart(): array
    {
        $raw = session(self::CART_SESSION_KEY, []);
        if (! is_array($raw)) {
            return [];
        }

        $cart = [];
        foreach ($raw as $productId => $qty) {
            $id = (int) $productId;
            $q = is_array($qty) ? (int) ($qty['quantity'] ?? 0) : (int) $qty;
            if ($id > 0 && $q > 0) {
                $cart[$id] = $q;
            }
        }

        return $cart;
    }

    /**
     * @return array{0: array<int, array{product: Product, quantity: int, available: int, subtotal: float}>, 1: float, 2: int}
     */
    private function buildCartSummary(InventoryLedgerService $ledger): array
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            return [[], 0.0, 0];
        }

        $products = Product::query()
            ->whereIn('id', array_keys($cart))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0.0;
        $count = 0;

        foreach ($cart as $productId => $qty) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $lineSubtotal = round((float) $product->selling_price * $qty, 2);
            $subtotal += $lineSubtotal;
            $count += $qty;

            $items[] = [
                'product' => $product,
                'quantity' => $qty,
                'available' => $ledger->availableStock($product->id),
                'subtotal' => $lineSubtotal,
            ];
        }

        return [$items, $subtotal, $count];
    }
}
