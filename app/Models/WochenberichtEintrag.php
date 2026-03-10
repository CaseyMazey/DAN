<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WochenberichtEintrag extends Model
{
    protected $table = 'wochenbericht_eintraege';
    protected $fillable = [
    'wochenbericht_id', 'kategorie', 'beschreibung', 'stunden'
];

    public function wochenbericht()
    {
        return $this->belongsTo(Wochenbericht::class);
    }
}