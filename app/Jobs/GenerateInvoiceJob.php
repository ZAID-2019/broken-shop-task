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

    // Retry a few times if invoice generation fails
    public $tries = 3;

    // Backoff to avoid hammering workers on repeated failures
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        // Reload order from DB
        $order = Order::query()->find($this->orderId);
        if (!$order) {
            return;
        }

        // Never generate invoice unless payment is confirmed
        if ($order->payment_status !== 'paid') {
            return;
        }

        // Use stored snapshot items (audit-friendly)
        $items = json_decode($order->items ?? '[]', true) ?: [];

        // Collect product ids to bulk-load DB records (optional fallback)
        $productIds = [];
        foreach ($items as $item) {
            if (isset($item['product_id'])) {
                $productIds[] = (int) $item['product_id'];
            }
        }

        // Bulk query to avoid N+1
        $products = Product::query()
            ->select(['id', 'name', 'price'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        // Build invoice lines from snapshot (DB is only fallback)
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

        // Mock "invoice generated" step (in real app: persist invoice/pdf)
        $order->status = 'invoiced';
        $order->save();
    }
}