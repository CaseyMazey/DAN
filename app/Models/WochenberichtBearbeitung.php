<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WochenberichtBearbeitung extends Model
{
    protected $table = 'wochenbericht_bearbeitungen';
    protected $fillable = [
        'wochenbericht_id', 'autor_id', 'kommentar'
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