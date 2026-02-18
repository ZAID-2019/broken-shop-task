<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $page = (int) $request->query('page', 1);
        $page = max(1, $page);

        $products = Product::query()
            ->select(['id', 'vendor_id', 'name', 'sku', 'price'])
            ->with('vendor:id,name')
            ->simplePaginate($perPage, ['*'], 'page', $page);

        foreach ($products as $p) {
            $p->computed = strtoupper($p->name) . ' - ' . ($p->vendor?->name ?? '');
        }


        return view('products.index', [
            'products' => $products,
            'title' => 'Products',
        ]);
    }

    public function show($id)
    {
        $product = Product::with('vendor')->findOrFail($id);

        return view('products.show', ['product' => $product, 'title' => $product->name]);
    }
}
