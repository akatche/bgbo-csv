<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'transaction_type',
        'date',
        'customer_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'sent_type',
        'sent',
        'sent_at',
        'reason',
        'original_data',
    ];

    protected $casts = [
        'date' => 'datetime',
        'sent' => 'boolean',
        'original_data' => 'array'
    ];
}
