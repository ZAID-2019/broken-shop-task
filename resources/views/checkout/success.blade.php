<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'Checkout Success' }}</title>

  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f7f7f7;
      margin: 0;
      padding: 30px;
      color: #111827;
    }

    .container {
      max-width: 720px;
      margin: 0 auto;
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      margin-bottom: 18px;
    }

    .btn {
      padding: 9px 14px;
      border: 1px solid transparent;
      cursor: pointer;
      text-decoration: none;
      font-size: 14px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #fff;
      color: #111;
      border-color: #e5e7eb;
    }

    .btn:hover {
      background: #f3f4f6;
    }

    .card {
      background: #fff;
      padding: 18px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
      border: 1px solid #f1f5f9;
    }

    h1 {
      margin: 0 0 10px;
      font-size: 22px;
    }

    .meta {
      color: #6b7280;
      font-size: 14px;
      margin: 6px 0;
    }

    .total {
      font-weight: 800;
      font-size: 18px;
      margin-top: 12px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 14px;
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
      display: inline-block;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin: 10px 0 15px;
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
  <div class="container">

    <div class="top-bar">
      <a href="/" class="btn">← Back to products</a>
      <a href="{{ route('cart.index') }}" class="btn">View cart</a>
    </div>

    @if(session('error'))
    <div class="alert error">{{ session('error') }}</div>
    @endif

    <div class="card">
      <h1>✅ Checkout Successful</h1>

      <div class="meta">
        <strong>Order ID:</strong> {{ $order->id }}
      </div>

      <div class="meta">
        <strong>Status:</strong> {{ $order->status }}
      </div>

      <div class="meta">
        <strong>Payment:</strong>
        <span class="badge">{{ $order->payment_status }}</span>
      </div>

      @php
      $items = json_decode($order->items ?? '[]', true) ?: [];
      @endphp

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
            <td>{{ $it['qty'] ?? 0 }}</td>
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