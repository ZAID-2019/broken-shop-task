<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'Products' }}</title>

  <style>
    body{
      font-family: Arial, sans-serif;
      background:#f7f7f7;
      margin:0;
      padding:30px;
      color:#111827;
    }

    h1{ margin:0 0 18px; }

    .top-bar{
      display:flex;
      justify-content:flex-end;
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

    .product-list{
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
      gap:14px;
    }

    .card{
      background:#fff;
      padding:14px;
      border-radius:10px;
      box-shadow:0 2px 10px rgba(0,0,0,.05);
      border:1px solid #f1f5f9;
      display:flex;
      flex-direction:column;
      gap:8px;
    }

    .title{
      font-weight:700;
      font-size:16px;
      line-height:1.2;
    }

    .meta{
      font-size:13px;
      color:#6b7280;
    }

    .price{
      font-weight:800;
      font-size:16px;
      margin-top:4px;
    }

    .actions{
      margin-top:auto;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
    }

    /* Pagination (works great with simplePaginate: Previous/Next) */
    .pagination{
      margin-top:26px;
      display:flex;
      justify-content:center;
    }

    .pagination nav{
      display:flex;
      gap:8px;
      align-items:center;
    }

    .pagination a,
    .pagination span{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:9px 12px;
      border-radius:10px;
      border:1px solid #e5e7eb;
      background:#fff;
      color:#111827;
      text-decoration:none;
      font-size:14px;
      min-width:92px;
    }

    .pagination a:hover{
      border-color:#2563eb;
      color:#2563eb;
    }

    .pagination .disabled span{
      background:#f3f4f6;
      color:#9ca3af;
      border-color:#e5e7eb;
      cursor:not-allowed;
    }

    /* Hide Tailwind "Showing x to y..." line if it appears */
    .pagination p{
      display:none;
    }
  </style>
</head>

<body>

  <h1>{{ $title ?? 'Products' }}</h1>

  <div class="top-bar">
    <a href="{{ route('cart.index') }}" class="btn btn-secondary">View Cart</a>
  </div>

  @if($products->isEmpty())
    <p>No products found.</p>
  @else
    <div class="product-list">
      @foreach($products as $p)
        <div class="card">
          <div class="title">{{ $p->name }}</div>

          @if(!empty($p->sku))
            <div class="meta">SKU: {{ $p->sku }}</div>
          @endif

          @if(isset($p->vendor))
            <div class="meta">Vendor: {{ $p->vendor->name }}</div>
          @endif

          <div class="price">${{ number_format($p->price, 2) }}</div>

          <div class="actions">
            <a href="/products/{{ $p->id }}" class="btn btn-secondary">View</a>

            <form method="POST" action="{{ route('cart.add', $p->id) }}">
              @csrf
              <button type="submit" class="btn btn-primary">Add to Cart</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  <div class="pagination">
    {{ $products->links() }}
  </div>

</body>
</html>