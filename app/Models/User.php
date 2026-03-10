<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'vorname',
        'nachname',
        'email',
        'password',
        'rolle',
        'ausbildungsbeginn',
        'ausbildungsende',
        'klasse_id',
        'abteilung',
        'aktiv',
        'dark_mode',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'ausbildungsbeginn' => 'date',
            'ausbildungsende'   => 'date',
            'dark_mode'         => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return $this->vorname . ' ' . $this->nachname;
    }
}