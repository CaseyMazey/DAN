<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aufgabe extends Model
{
    protected $table = 'aufgaben';

    protected $fillable = [
        'azubi_id', 'ausbilder_id', 'titel', 'beschreibung',
        'kategorie', 'stunden_abziehen_bei', 'stunden',
        'status', 'archiviert', 'archiviert_am', 'in_wochenbericht'
    ];

    protected $casts = [
        'archiviert'      => 'boolean',
        'in_wochenbericht'=> 'boolean',
        'archiviert_am'   => 'datetime',
        'stunden'         => 'float',
    ];

    public function azubi()
    {
        return $this->belongsTo(User::class, 'azubi_id');
    }

    public function ausbilder()
    {
        return $this->belongsTo(User::class, 'ausbilder_id');
    }
}