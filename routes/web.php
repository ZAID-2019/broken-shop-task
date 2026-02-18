<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminController;

Route::get('/', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::post('/cart/add/{id}', [OrderController::class, 'addToCart'])->name('cart.add');
Route::get('/cart', [OrderController::class, 'cart'])->name('cart.index');
Route::post('/checkout', [OrderController::class, 'checkout'])->name('checkout');
Route::get('/checkout/success/{order}', [OrderController::class, 'success'])->name('checkout.success');

Route::post('/ticket', [TicketController::class, 'store']);

Route::get('/admin', [AdminController::class, 'index']);
