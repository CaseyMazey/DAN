<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wochenbericht extends Model
{
    protected $table = 'wochenberichte';
    protected $fillable = [
        'azubi_id', 'startdatum', 'enddatum',
        'abgegeben', 'abgegeben_am', 'auto_abgeschickt',
        'azubi_signiert', 'azubi_signiert_am',
        'signiert', 'signiert_am',
        'bearbeitet', 'gesperrt'
    ];

    protected $casts = [
        'abgegeben'       => 'boolean',
        'auto_abgeschickt'=> 'boolean',
        'azubi_signiert'  => 'boolean',
        'signiert'        => 'boolean',
        'bearbeitet'      => 'boolean',
        'gesperrt'        => 'boolean',
        'abgegeben_am'    => 'datetime',
        'azubi_signiert_am' => 'datetime',
        'signiert_am'     => 'datetime',
        'startdatum'      => 'date',
        'enddatum'        => 'date',
    ];

    // Beziehungen
    public function azubi()
    {
        return $this->belongsTo(User::class, 'azubi_id');
    }

    public function eintraege()
    {
        return $this->hasMany(WochenberichtEintrag::class);
    }

    public function kommentare()
    {
        return $this->hasMany(WochenberichtKommentar::class);
    }

    public function bearbeitungen()
    {
        return $this->hasMany(WochenberichtBearbeitung::class);
    }

    // Hilfsfunktionen
    public function istAbgegeben(): bool
    {
        return $this->abgegeben;
    }

    public function istGesperrt(): bool
    {
        return $this->gesperrt;
    }

    public function gesamtstunden(): float
    {
        return $this->eintraege->sum('stunden');
    }
}