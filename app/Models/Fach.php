<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fach extends Model
{
    protected $table = 'faecher';

    protected $fillable = ['azubi_id', 'name'];

    public function azubi()
    {
        return $this->belongsTo(User::class, 'azubi_id');
    }
}