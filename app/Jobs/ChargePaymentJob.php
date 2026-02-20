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
use Illuminate\Support\Facades\DB;
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
        $updated = Order::where('id', $this->order->id)
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'processing']);

        if ($updated === 0) {
            return;
        }

        $orderId = (int) $this->order->id;

        Log::info('Charging order', ['order_id' => $orderId]);

        // Mock provider result (explicit success/failure)
        $isSuccess = random_int(1, 100) <= 90;
        $reference = $isSuccess ? ('PAY-' . time() . '-' . random_int(1000, 9999)) : null;
        $providerResponse = ['ok' => $isSuccess, 'ref' => $reference];

        $shouldDispatchInvoice = false;

        DB::transaction(function () use ($orderId, $isSuccess, $reference, $providerResponse, &$shouldDispatchInvoice) {
            $order = Order::query()->lockForUpdate()->find($orderId);
            if (!$order) {
                return;
            }

            // Only continue if still processing (ensures correct transition)
            if ($order->payment_status !== 'processing') {
                return;
            }

            PaymentAttempt::create([
                'order_id' => $order->id,
                'provider' => 'mockpay',
                'reference' => $reference,
                'status' => $isSuccess ? 'success' : 'failed',
                'request_payload' => json_encode(['amount' => $order->total]),
                'response_payload' => json_encode($providerResponse),
            ]);

            if ($isSuccess) {
                $order->payment_reference = $reference;
                $order->payment_status = 'paid';
                $order->status = 'processing';
                $order->save();
                $shouldDispatchInvoice = true;
                return;
            }

            // Failure terminal state (avoid stuck processing)
            $order->payment_status = 'failed';
            $order->status = 'payment_failed';
            $order->save();
        });

        if ($shouldDispatchInvoice) {
            GenerateInvoiceJob::dispatch($orderId)->onQueue('default');
        }
    }
}
