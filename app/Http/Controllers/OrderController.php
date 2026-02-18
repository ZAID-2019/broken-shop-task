<?php

namespace App\Http\Controllers;

use App\Jobs\ChargePaymentJob;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    public function addToCart(Request $request, $id)
    {
        // Fail fast if product does not exist
        $product = Product::query()
            ->select(['id'])
            ->findOrFail((int) $id);

        // Minimal session cart: [product_id => qty]
        $cart = $request->session()->get('cart', []);

        // Increment safely (cap to prevent abuse)
        $currentQty = (int) ($cart[$product->id] ?? 0);
        $cart[$product->id] = min(50, $currentQty + 1);

        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index');
    }

    public function cart(Request $request)
    {
        // Session cart structure: [product_id => qty]
        $cart = $request->session()->get('cart', []);

        // Always render same view for consistency
        if (empty($cart)) {
            return view('cart.index', [
                'items' => [],
                'total' => 0,
            ]);
        }

        $productIds = array_keys($cart);

        // DB is source of truth (price + product existence)
        $products = Product::query()
            ->select(['id', 'name', 'sku', 'price'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $items = [];
        $total = 0.0;

        foreach ($cart as $productId => $qty) {
            $qty = (int) $qty;

            if ($qty < 1 || !isset($products[$productId])) {
                continue;
            }

            $p = $products[$productId];
            $price = (float) $p->price;
            $subtotal = $price * $qty;

            $items[] = [
                'id' => (int) $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }

        return view('cart.index', [
            'items' => $items,
            'total' => $total,
        ]);
    }
    public function checkout(Request $request)
    {
        $cart = $request->session()->get('cart', []);

        // No checkout without items
        if (empty($cart)) {
            return response()->json(['ok' => false, 'message' => 'Cart is empty'], 422);
        }

        // Prevent double-submit (best-effort, session-scoped)
        if ($request->session()->get('checkout_lock') === true) {
            return response()->json(['ok' => false, 'message' => 'Checkout already in progress'], 409);
        }
        $request->session()->put('checkout_lock', true);

        try {
            $productIds = array_keys($cart);

            // DB is source of truth (never trust session for prices)
            $products = Product::query()
                ->select(['id', 'name', 'sku', 'price'])
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $items = [];
            $total = 0.0;

            foreach ($cart as $productId => $qty) {
                $qty = (int) $qty;

                if ($qty < 1 || !isset($products[$productId])) {
                    continue;
                }

                $p = $products[$productId];
                $unitPrice = (float) $p->price;

                $lineTotal = $unitPrice * $qty;

                // Persist snapshot for audit/invoicing even if product changes later
                $items[] = [
                    'product_id' => (int) $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'unit_price' => $unitPrice,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];

                $total += $lineTotal;
            }

            if (empty($items)) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }

            DB::beginTransaction();

            $order = new Order();
            // Guest flow: never accept user_id from request
            $order->user_id = null;

            $order->items = json_encode($items);
            $order->total = (string) $total;
            $order->status = 'new';
            $order->payment_status = 'pending';
            $order->save();

            // Dispatch after commit so workers never process rolled-back orders
            DB::afterCommit(fn() => ChargePaymentJob::dispatch($order)->onQueue('default'));

            DB::commit();

            $request->session()->forget('cart');
            $request->session()->put('last_order_id', $order->id);

            return redirect()->route('checkout.success', $order->id);
        } finally {
            // Always release lock (success or failure)
            $request->session()->forget('checkout_lock');
        }
    }

    public function success(Request $request, Order $order)
    {
        $lastOrderId = (int) $request->session()->get('last_order_id', 0);

        if ($lastOrderId <= 0) {
            return redirect('/')->with('error', 'Order not found.');
        }

        if ($order->id !== $lastOrderId) {
            if (!Order::whereKey($lastOrderId)->exists()) {
                return redirect('/')->with('error', 'Order not found.');
            }

            // redirect silently (no error)
            return redirect()->route('checkout.success', $lastOrderId);
        }

        return view('checkout.success', [
            'order' => $order,
            'items' => json_decode($order->items ?? '[]', true) ?: [],
            'title' => 'Checkout Success',
        ]);
    }
}
