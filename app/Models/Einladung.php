<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Einladung extends Model
{
    protected $table = 'einladungen';

    protected $fillable = [
        'email', 'token', 'rolle', 'klasse_id', 'eingeladen_von', 'expires_at', 'used_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function klasse()
    {
        return $this->belongsTo(Klasse::class);
    }

    public function eingeladenVon()
    {
        return $this->belongsTo(User::class, 'eingeladen_von');
    }

    public function istAbgelaufen(): bool
    {
        return $this->expires_at->isPast();
    }

    public function istGenutzt(): bool
    {
        return $this->used_at !== null;
    }

    public function istGueltig(): bool
    {
        return !$this->istAbgelaufen() && !$this->istGenutzt();
    }
}
