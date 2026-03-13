<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aufgabe;
use App\Models\Wochenbericht;
use App\Models\KalenderEintrag;
use App\Models\Klasse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $rolle = $user->rolle;

        if ($rolle === 'azubi') {
            $aufgabenStats = [
                'geplant'       => Aufgabe::where('azubi_id', $user->id)->where('archiviert', false)->where('status', 'geplant')->count(),
                'in_bearbeitung'=> Aufgabe::where('azubi_id', $user->id)->where('archiviert', false)->where('status', 'in_bearbeitung')->count(),
                'fertig'        => Aufgabe::where('azubi_id', $user->id)->where('archiviert', false)->where('status', 'fertig')->count(),
            ];

            $stundenDieseWoche = Aufgabe::where('azubi_id', $user->id)
                ->where('archiviert', false)
                ->whereIn('status', ['in_bearbeitung', 'fertig'])
                ->sum('stunden');

            $naechsteTermine = KalenderEintrag::where('azubi_id', $user->id)
                ->where('datum', '>=', Carbon::today())
                ->orderBy('datum')
                ->limit(5)
                ->get();

            $nichtSigniert = Wochenbericht::where('azubi_id', $user->id)
                ->where('abgegeben', true)
                ->where('azubi_signiert', false)
                ->count();

            $ausstehendAusbilder = Wochenbericht::where('azubi_id', $user->id)
                ->where('azubi_signiert', true)
                ->where('signiert', false)
                ->count();

            return view('dashboard.azubi', compact(
                'aufgabenStats',
                'stundenDieseWoche',
                'naechsteTermine',
                'nichtSigniert',
                'ausstehendAusbilder'
            ));
        }

        if ($rolle === 'ausbilder') {
            // Eigene Klassen mit Azubis
            $klassen = $user->klassen()->with(['azubis'])->get();

            // Azubi-IDs aus allen eigenen Klassen
            $azubiIds = $klassen->flatMap(fn($k) => $k->azubis->pluck('id'));

            // Ausstehende Signaturen (Azubi hat signiert, Ausbilder noch nicht)
            $ausstehendSignaturen = Wochenbericht::whereIn('azubi_id', $azubiIds)
                ->where('azubi_signiert', true)
                ->where('signiert', false)
                ->with('azubi')
                ->orderBy('startdatum', 'desc')
                ->get();

            // Berichte mit Bearbeitet-Tag
            $bearbeitetBerichte = Wochenbericht::whereIn('azubi_id', $azubiIds)
                ->where('bearbeitet', true)
                ->where('signiert', false)
                ->with('azubi')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get();

            // Pro Klasse: Azubi-Übersicht mit letztem Berichtsstatus
            $klassenUebersicht = $klassen->map(function ($klasse) {
                $azubis = $klasse->azubis->map(function ($azubi) {
                    $letzterBericht = Wochenbericht::where('azubi_id', $azubi->id)
                        ->orderBy('startdatum', 'desc')
                        ->first();
                    return [
                        'azubi'         => $azubi,
                        'letzterBericht'=> $letzterBericht,
                    ];
                });
                return [
                    'klasse' => $klasse,
                    'azubis' => $azubis,
                ];
            });

            return view('dashboard.ausbilder', compact(
                'klassen',
                'ausstehendSignaturen',
                'bearbeitetBerichte',
                'klassenUebersicht'
            ));
        }

        return match($rolle) {
            'admin'  => view('dashboard.admin'),
            default  => abort(403)
        };
    }
}
