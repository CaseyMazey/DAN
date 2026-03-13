<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wochenbericht;
use App\Models\WochenberichtEintrag;
use App\Models\Aufgabe;
use App\Models\User;
use Carbon\Carbon;

class WochenberichtAbgeben extends Command
{
    protected $signature = 'wochenbericht:abgeben';
    protected $description = 'Gibt alle offenen Wochenberichte automatisch ab (freitags 20:00)';

    public function handle()
    {
        $startDerWoche = Carbon::now()->startOfWeek()->toDateString();

        $azubis = User::where('rolle', 'azubi')->where('aktiv', true)->get();

        foreach ($azubis as $azubi) {
            $bericht = Wochenbericht::firstOrCreate(
                ['azubi_id' => $azubi->id, 'startdatum' => $startDerWoche],
                ['enddatum' => Carbon::now()->endOfWeek()->subDays(2)->toDateString()]
            );

            if ($bericht->abgegeben) continue;

            // Snapshot: nur in_bearbeitung + fertig (nicht geplant)
            $aufgaben = Aufgabe::where('azubi_id', $azubi->id)
                ->where('archiviert', false)
                ->whereIn('status', ['in_bearbeitung', 'fertig'])
                ->get();

            foreach ($aufgaben as $aufgabe) {
                WochenberichtEintrag::create([
                    'wochenbericht_id' => $bericht->id,
                    'kategorie'        => $aufgabe->kategorie,
                    'beschreibung'     => $aufgabe->titel . ($aufgabe->beschreibung ? ': ' . $aufgabe->beschreibung : ''),
                    'stunden'          => $aufgabe->stunden,
                ]);

                if ($aufgabe->status === 'fertig') {
                    $aufgabe->update([
                        'in_wochenbericht' => true,
                        'archiviert'       => true,
                        'archiviert_am'    => now(),
                    ]);
                } else {
                    // in_bearbeitung: bleibt erhalten
                    $aufgabe->update(['in_wochenbericht' => true]);
                }
            }

            $bericht->update([
                'abgegeben'        => true,
                'abgegeben_am'     => now(),
                'auto_abgeschickt' => true,
            ]);

            $this->info("Bericht abgegeben für: {$azubi->full_name}");
        }

        $this->info('Fertig.');
    }
}