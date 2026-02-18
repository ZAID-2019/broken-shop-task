<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAttempt extends Model
{
    // Explicitly allow only safe fields for mass assignment
    protected $fillable = [
        'order_id',
        'provider',
        'reference',
        'status',
        'request_payload',
        'response_payload',
    ];
}