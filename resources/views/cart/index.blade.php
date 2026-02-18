<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cart</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            margin: 0;
            padding: 30px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .btn {
            padding: 8px 14px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            border-radius: 4px;
        }

        .btn-primary {
            background: #16a34a;
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #111;
        }

        .card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            margin-bottom: 15px;
        }

        .meta {
            font-size: 13px;
            color: #555;
        }

        .price {
            font-weight: bold;
            margin-top: 5px;
        }

        .total-box {
            margin-top: 25px;
            padding: 15px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            text-align: right;
        }

        .empty {
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<h1>Your Cart</h1>



{{-- Flash Messages --}}
@if(session('error'))
    <div class="alert error">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert success">
        {{ session('success') }}
    </div>
@endif

<div class="top-bar">
    <a href="/" class="btn btn-secondary">← Back to Products</a>
</div>

@if(empty($items))
    <div class="empty">
        <p>Your cart is empty.</p>
    </div>
@else

    @foreach($items as $item)
        <div class="card">
            <strong>{{ $item['name'] }}</strong>

            <div class="meta">SKU: {{ $item['sku'] }}</div>

            <div class="meta">
                Quantity: {{ $item['qty'] }}
            </div>

            <div class="meta">
                Unit Price: ${{ number_format($item['price'], 2) }}
            </div>

            <div class="price">
                Subtotal: ${{ number_format($item['subtotal'], 2) }}
            </div>
        </div>
    @endforeach

    <div class="total-box">
        <h3>Total: ${{ number_format($total, 2) }}</h3>

        <form method="POST" action="{{ route('checkout') }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                Checkout
            </button>
        </form>
    </div>

@endif

</body>
</html>