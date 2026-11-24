<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['datos' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (BookingEvent $evento) {
            $evento->created_at ??= now();
        });
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public function reserva()
    {
        return $this->belongsTo(Reserva::class)->withTrashed();
    }
}
