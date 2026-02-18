<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if (!$order) {
            return;
        }

        // Only generate invoice for paid orders
        if ($order->payment_status !== 'paid') {
            return;
        }

        $items = json_decode($order->items ?? '[]', true) ?: [];

        $productIds = [];
        foreach ($items as $item) {
            if (isset($item['product_id'])) {
                $productIds[] = (int) $item['product_id'];
            }
        }

        $products = Product::query()
            ->select(['id', 'name', 'price'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $lines = [];
        foreach ($items as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $p = $products[$pid] ?? null;

            $lines[] = [
                'product_id' => $pid,
                'name' => $p?->name ?? ($item['name'] ?? null),
                'unit_price' => (float) ($item['unit_price'] ?? ($p?->price ?? 0)),
                'qty' => (int) ($item['qty'] ?? 0),
                'line_total' => (float) ($item['line_total'] ?? 0),
            ];
        }

        // Pretend invoice generation (store result somewhere if needed)
        $order->status = 'invoiced';
        $order->save();
    }
}