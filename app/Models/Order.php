<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // Explicit allow-list to prevent mass assignment vulnerabilities
    protected $fillable = [
        'user_id',
        'items',
        'total',
        'status',
        'payment_reference',
        'payment_status',
    ];
}
