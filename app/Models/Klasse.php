<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Klasse extends Model
{
    protected $table = 'klassen';

    protected $fillable = ['name', 'jahrgang', 'aktiv', 'abteilung_id'];

    protected $casts = [
        'aktiv' => 'boolean',
    ];

    public function azubis()
    {
        return $this->hasMany(User::class, 'klasse_id');
    }

    public function ausbilder()
    {
        return $this->belongsToMany(User::class, 'ausbilder_klassen', 'klasse_id', 'user_id');
    }

    public function abteilung()
    {
        return $this->belongsTo(Abteilung::class);
    }
}
