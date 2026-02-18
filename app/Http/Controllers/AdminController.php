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
        $expected = (string) env('ADMIN_TOKEN', '');
        $provided = (string) $request->header('X-ADMIN-TOKEN', '');

        if ($expected === '' || !hash_equals($expected, $provided)) {
            abort(403);
        }

        Log::info('Admin access', [
            'ip' => $request->ip(),
            'ua' => substr((string) $request->userAgent(), 0, 120),
        ]);

        $orders = Order::query()->latest()->limit(200)->get();
        $tickets = Ticket::query()->latest()->limit(200)->get();

        return response()->json([
            'orders' => $orders,
            'tickets' => $tickets,
        ]);
    }
}