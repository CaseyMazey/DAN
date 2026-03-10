<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WochenberichtKommentar extends Model
{
    protected $table = 'wochenbericht_kommentare';
    protected $fillable = [
        'wochenbericht_id', 'autor_id', 'inhalt'
    ];

    public function wochenbericht()
    {
        return $this->belongsTo(Wochenbericht::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}