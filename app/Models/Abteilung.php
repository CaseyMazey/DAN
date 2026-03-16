<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abteilung extends Model
{
    protected $table = 'abteilungen';

    protected $fillable = [
        'name',
        'kuerzel',
        'beschreibung',
        'aktiv',
    ];

    protected $casts = [
        'aktiv' => 'boolean',
    ];

    public function klassen()
    {
        return $this->hasMany(Klasse::class);
    }
}
