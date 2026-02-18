<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'Order Summary' }}</title>

  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f7f7f7;
      margin: 0;
      padding: 30px;
      color: #111827;
    }

    .container {
      max-width: 750px;
      margin: 0 auto;
    }

    .card {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
      border: 1px solid #f1f5f9;
    }

    h1 {
      margin: 0 0 12px;
      font-size: 22px;
    }

    .meta {
      font-size: 14px;
      color: #6b7280;
      margin: 6px 0;
    }

    .total {
      font-weight: bold;
      font-size: 18px;
      margin-top: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th,
    td {
      text-align: left;
      padding: 10px;
      border-bottom: 1px solid #e5e7eb;
      font-size: 14px;
    }

    th {
      color: #374151;
    }

    .badge {
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      display: inline-block;
      border: 1px solid transparent;
    }

    .badge.pending {
      background: #fef3c7;
      color: #92400e;
      border-color: #fde68a;
    }

    .badge.processing {
      background: #dbeafe;
      color: #1e40af;
      border-color: #bfdbfe;
    }

    .badge.paid {
      background: #dcfce7;
      color: #065f46;
      border-color: #86efac;
    }

    .message {
      margin-top: 10px;
      font-size: 14px;
    }

    .top-links {
      margin-bottom: 15px;
      display: flex;
      justify-content: space-between;
      gap: 10px;
    }

    .btn {
      padding: 8px 12px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      border: 1px solid #e5e7eb;
      background: #fff;
      color: #111;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn:hover {
      background: #f3f4f6;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin: 0 0 12px;
      font-size: 14px;
    }

    .alert.error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }
  </style>
</head>

<body>

  @php
  // Whitelist badge class so we never print unexpected CSS classes
  $badgeClass = match ($order->payment_status) {
  'pending' => 'pending',
  'processing' => 'processing',
  'paid' => 'paid',
  default => 'pending',
  };
  @endphp

  <div class="container">

    <div class="top-links">
      <a href="/" class="btn">← Back to products</a>

      <div style="display:flex; gap:10px;">
        <a href="{{ url()->current() }}" class="btn">↻ Refresh status</a>
        <a href="{{ route('cart.index') }}" class="btn">View cart</a>
      </div>
    </div>

    @if(session('error'))
    <div class="alert error">{{ session('error') }}</div>
    @endif

    <div class="card">

      <h1>Order Summary</h1>

      <div class="meta">
        <strong>Order ID:</strong> {{ $order->id }}
      </div>

      <div class="meta">
        <strong>Status:</strong> {{ $order->status }}
      </div>

      <div class="meta">
        <strong>Payment:</strong>
        <span class="badge {{ $badgeClass }}">{{ ucfirst($order->payment_status) }}</span>
      </div>

      @if($order->payment_status === 'paid' && !empty($order->payment_reference))
      <div class="meta">
        <strong>Payment Ref:</strong> {{ $order->payment_reference }}
      </div>
      @endif

      {{-- Dynamic payment message --}}
      @if($order->payment_status === 'pending')
      <div class="message">Your payment is being prepared. Please wait while we process it.</div>
      @elseif($order->payment_status === 'processing')
      <div class="message">Payment is currently being processed.</div>
      @elseif($order->payment_status === 'paid')
      <div class="message">Payment completed successfully. 🎉</div>
      @else
      <div class="message">Payment status updated. Please refresh.</div>
      @endif

      @if(!empty($items))
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>SKU</th>
            <th>Qty</th>
            <th>Unit</th>
            <th>Line Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $it)
          <tr>
            <td>{{ $it['name'] ?? '-' }}</td>
            <td>{{ $it['sku'] ?? '-' }}</td>
            <td>{{ (int)($it['qty'] ?? 0) }}</td>
            <td>${{ number_format((float)($it['unit_price'] ?? 0), 2) }}</td>
            <td>${{ number_format((float)($it['line_total'] ?? 0), 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif

      <div class="total">
        Total: ${{ number_format((float)($order->total ?? 0), 2) }}
      </div>

    </div>
  </div>

</body>

</html>