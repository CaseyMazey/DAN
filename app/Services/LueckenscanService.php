<?php

namespace App\Services;

use App\Models\Wochenbericht;
use App\Models\User;
use Carbon\Carbon;

class LueckenscanService
{
    public function scan(User $user): int
    {
        if (!$user->ausbildungsbeginn) {
            return 0;
        }

        $start = $user->ausbildungsbeginn->startOfWeek();
        $heute = Carbon::now()->startOfWeek();
        $angelegt = 0;

        while ($start->lte($heute)) {
            $enddatum = $start->copy()->endOfWeek()->subDays(2); // Freitag

            Wochenbericht::firstOrCreate(
                [
                    'azubi_id'   => $user->id,
                    'startdatum' => $start->toDateString(),
                ],
                [
                    'enddatum' => $enddatum->toDateString(),
                ]
            );

            $angelegt++;
            $start->addWeek();
        }

        return $angelegt;
    }
}