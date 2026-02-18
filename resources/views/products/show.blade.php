<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title ?? ($product->name ?? 'Product') }}</title>

  <style>
    body{
      font-family: Arial, sans-serif;
      background:#f7f7f7;
      margin:0;
      padding:30px;
      color:#111827;
    }

    .container{
      max-width: 720px;
      margin: 0 auto;
    }

    .top-bar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:18px;
    }

    .btn{
      padding:9px 14px;
      border:1px solid transparent;
      cursor:pointer;
      text-decoration:none;
      font-size:14px;
      border-radius:8px;
      display:inline-flex;
      align-items:center;
      gap:8px;
    }

    .btn-primary{
      background:#2563eb;
      color:#fff;
    }
    .btn-primary:hover{ opacity:.95; }

    .btn-secondary{
      background:#fff;
      color:#111;
      border-color:#e5e7eb;
    }
    .btn-secondary:hover{ background:#f3f4f6; }

    .card{
      background:#fff;
      padding:18px;
      border-radius:10px;
      box-shadow:0 2px 10px rgba(0,0,0,.05);
      border:1px solid #f1f5f9;
    }

    h1{
      margin:0 0 10px;
      font-size:22px;
      line-height:1.2;
    }

    .meta{
      font-size:14px;
      color:#6b7280;
      margin:6px 0;
    }

    .price{
      margin-top:12px;
      font-weight:800;
      font-size:18px;
    }

    .actions{
      margin-top:18px;
      display:flex;
      gap:10px;
      align-items:center;
    }
  </style>
</head>

<body>
  <div class="container">

    <div class="top-bar">
      <a href="/" class="btn btn-secondary">← Back to products</a>
      <a href="{{ route('cart.index') }}" class="btn btn-secondary">View Cart</a>
    </div>

    <div class="card">
      <h1>{{ $product->name }}</h1>

      @if(!empty($product->sku))
        <div class="meta"><strong>SKU:</strong> {{ $product->sku }}</div>
      @endif

      @if(isset($product->vendor))
        <div class="meta"><strong>Vendor:</strong> {{ $product->vendor->name }}</div>
      @endif

      <div class="price">
        ${{ number_format((float)$product->price, 2) }}
      </div>

      <div class="actions">
        <form method="POST" action="{{ route('cart.add', $product->id) }}">
          @csrf
          <button type="submit" class="btn btn-primary">Add to Cart</button>
        </form>
      </div>
    </div>

  </div>
</body>
</html>