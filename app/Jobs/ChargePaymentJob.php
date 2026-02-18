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

    // Limit retries to avoid retry storms against payment provider
    public $tries = 3;

    // Progressive backoff (5s, 15s, 30s) instead of hammering immediately
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    // Prevent long-running jobs from blocking workers
    public $timeout = 30;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        // Atomic status transition:
        // Only process orders still in "pending" state.
        // This prevents double-charging if multiple workers run the same job.
        $updated = Order::where('id', $this->order->id)
            ->where('payment_status', 'pending')
            ->update([
                'payment_status' => 'processing'
            ]);

        // If no row updated, another worker already processed it
        if ($updated === 0) {
            return;
        }

        // Reload fresh state from DB
        $order = Order::find($this->order->id);

        if (!$order) {
            return;
        }

        // Log minimal info (avoid sensitive payload logging)
        Log::info('Charging order', [
            'order_id' => $order->id,
            'total' => $order->total,
        ]);

        // Simulate payment provider response
        $reference = 'PAY-' . time() . '-' . rand(1000, 9999);

        // Store payment attempt for audit trail
        PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'mockpay',
            'reference' => $reference,
            'status' => 'success',
            'request_payload' => json_encode(['amount' => $order->total]),
            'response_payload' => json_encode(['ok' => true, 'ref' => $reference]),
        ]);

        // Mark order as paid
        $order->payment_reference = $reference;
        $order->payment_status = 'paid';
        $order->status = 'processing';
        $order->save();

        // Continue async pipeline (invoice generation)
        GenerateInvoiceJob::dispatch($order->id)->onQueue('default');
    }
}
