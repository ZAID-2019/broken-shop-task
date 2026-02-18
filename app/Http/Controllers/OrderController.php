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
    /**
     * Session cart structure:
     * cart = [ product_id => qty, ... ]
     */

    public function addToCart(Request $request, $id)
    {
        // 1) Ensure product exists (prevents adding null / invalid ids)
        $product = Product::query()
            ->select(['id'])
            ->findOrFail((int) $id);

        // 2) Load cart (or create empty)
        $cart = $request->session()->get('cart', []);

        // 3) Increase qty (cap to prevent abuse / giant session payload)
        $currentQty = (int) ($cart[$product->id] ?? 0);
        $cart[$product->id] = min(50, $currentQty + 1);

        // 4) Save back to session
        $request->session()->put('cart', $cart);

        // Better UX: redirect to cart page
        return redirect()->route('cart.index');
    }

    public function cart(Request $request)
    {
        $cart = $request->session()->get('cart', []);

        // Always render the same view
        if (empty($cart)) {
            return view('cart.index', [
                'items' => [],
                'total' => 0,
            ]);
        }

        $productIds = array_keys($cart);

        // Load from DB (source of truth for price + product existence)
        $products = Product::query()
            ->select(['id', 'name', 'sku', 'price'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $items = [];
        $total = 0.0;

        foreach ($cart as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty < 1) {
                continue;
            }

            // If product removed from DB, skip safely
            if (!isset($products[$productId])) {
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

        if (empty($cart)) {
            return response()->json(['ok' => false, 'message' => 'Cart is empty'], 422);
        }

        // Simple protection against double submit / retries
        if ($request->session()->get('checkout_lock') === true) {
            return response()->json(['ok' => false, 'message' => 'Checkout already in progress'], 409);
        }
        $request->session()->put('checkout_lock', true);

        try {
            $productIds = array_keys($cart);

            // Load products from DB (payment safety: never trust session for prices)
            $products = Product::query()
                ->select(['id', 'name', 'sku', 'price'])
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $items = [];
            $total = 0.0;

            foreach ($cart as $productId => $qty) {
                $qty = (int) $qty;
                if ($qty < 1) {
                    continue;
                }

                if (!isset($products[$productId])) {
                    continue; // product deleted/not found
                }

                $p = $products[$productId];
                $unitPrice = (float) $p->price;
                $lineTotal = $unitPrice * $qty;

                $items[] = [
                    'product_id' => (int) $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'unit_price' => $unitPrice,   // snapshot
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];

                $total += $lineTotal;
            }

            if (empty($items)) {
                return redirect()
                    ->route('cart.index')
                    ->with('error', 'Your cart is empty.');
            }

            DB::beginTransaction();

            $order = new Order();
            // Guest flow (no auth): do NOT accept user_id from request
            $order->user_id = null;

            $order->items = json_encode($items);
            $order->total = (string) $total;
            $order->status = 'new';
            $order->payment_status = 'pending';
            $order->save();

            // Dispatch after commit to avoid race conditions
            DB::afterCommit(function () use ($order) {
                ChargePaymentJob::dispatch($order)->onQueue('default');
            });

            DB::commit();

            // Clear cart after successful order creation
            $request->session()->forget('cart');

            $request->session()->put('last_order_id', $order->id);

            return redirect()->route('checkout.success', $order->id);
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            // Always release lock
            $request->session()->forget('checkout_lock');
        }
    }

public function success(Request $request, Order $order)
{
    $lastOrderId = (int) $request->session()->get('last_order_id', 0);

    if ($lastOrderId === 0) {
        return redirect('/')
            ->with('error', 'No recent order found for this session.');
    }

    if ($order->id !== $lastOrderId) {
        return redirect()
            ->route('checkout.success', $lastOrderId)
            ->with('error', 'You can only view the latest order for this session.');
    }

    return view('checkout.success', [
        'order' => $order,
        'title' => 'Checkout Success',
    ]);
}
}
