<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // Read admin token from .env
        $expected = (string) env('ADMIN_TOKEN', '');

        // Token must be sent in request header
        $provided = (string) $request->header('X-ADMIN-TOKEN', '');

        // Block access if token invalid
        if ($expected === '' || !hash_equals($expected, $provided)) {
            abort(403);
        }

        // Log access (basic audit)
        Log::info('Admin access', [
            'ip' => $request->ip(),
        ]);

        // Load latest records (limit to avoid heavy queries)
        $orders = Order::latest()->limit(200)->get();
        $tickets = Ticket::latest()->limit(200)->get();

        return response()->json([
            'orders' => $orders,
            'tickets' => $tickets,
        ]);
    }
}
