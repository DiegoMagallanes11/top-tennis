<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingNotification extends Model
{
    protected $guarded = [];

    protected $casts = ['datos' => 'array', 'enviado_at' => 'datetime'];
}
