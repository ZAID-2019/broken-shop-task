<?php

namespace App\Jobs;

use App\Jobs\GenerateInvoiceJob;
use App\Models\Order;
use App\Models\PaymentAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChargePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Reduce retry attempts to avoid retry storms
    public $tries = 3;

    // Add progressive backoff instead of immediate retries
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    // Shorter timeout to avoid blocking workers
    public $timeout = 30;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        // Attempt atomic transition: pending -> processing
        $updated = Order::where('id', $this->order->id)
            ->where('payment_status', 'pending')
            ->update([
                'payment_status' => 'processing'
            ]);

        // If 0 rows updated, another worker already handled it
        if ($updated === 0) {
            return;
        }

        $order = Order::find($this->order->id);

        if (!$order) {
            return;
        }

        Log::info('Charging order', [
            'order_id' => $order->id,
            'total' => $order->total,
        ]);

        $reference = 'PAY-' . time() . '-' . rand(1000, 9999);

        PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'mockpay',
            'reference' => $reference,
            'status' => 'success',
            'request_payload' => json_encode(['amount' => $order->total]),
            'response_payload' => json_encode(['ok' => true, 'ref' => $reference]),
        ]);

        $order->payment_reference = $reference;
        $order->payment_status = 'paid';
        $order->status = 'processing';
        $order->save();

        GenerateInvoiceJob::dispatch($order->id)->onQueue('default');
    }
}
