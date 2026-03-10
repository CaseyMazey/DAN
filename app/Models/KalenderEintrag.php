<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KalenderEintrag extends Model
{
    protected $table = 'kalender_eintraege';

    protected $fillable = [
        'azubi_id', 'fach', 'typ', 'datum', 'thema', 'lernnotizen', 'note'
    ];

    protected $casts = [
        'datum' => 'date',
        'note'  => 'float',
    ];

    public function azubi()
    {
        return $this->belongsTo(User::class, 'azubi_id');
    }
}