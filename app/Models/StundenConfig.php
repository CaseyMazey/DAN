<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StundenConfig extends Model
{
    protected $table = 'stunden_config';

    protected $fillable = [
        'azubi_id', 'max_stunden', 'betrieb_stunden', 'berufsschule_stunden'
    ];

    protected $casts = [
        'max_stunden'          => 'float',
        'betrieb_stunden'      => 'float',
        'berufsschule_stunden' => 'float',
    ];

    public function azubi()
    {
        return $this->belongsTo(User::class, 'azubi_id');
    }
}