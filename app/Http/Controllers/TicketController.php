<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        // Validate only allowed fields
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'email' => 'nullable|email',
        ]);

        // Create using only validated fields
        $ticket = Ticket::create($validated);

        return response()->json([
            'ok' => true,
            'ticket_id' => $ticket->id,
        ]);
    }
}
