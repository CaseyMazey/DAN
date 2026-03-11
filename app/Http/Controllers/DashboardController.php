<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aufgabe;
use App\Models\Wochenbericht;
use App\Models\KalenderEintrag;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $rolle = $user->rolle;

        if ($rolle === 'azubi') {
            // Aufgaben-Übersicht
            $aufgabenStats = [
                'geplant'       => Aufgabe::where('azubi_id', $user->id)->where('archiviert', false)->where('status', 'geplant')->count(),
                'in_bearbeitung'=> Aufgabe::where('azubi_id', $user->id)->where('archiviert', false)->where('status', 'in_bearbeitung')->count(),
                'fertig'        => Aufgabe::where('azubi_id', $user->id)->where('archiviert', false)->where('status', 'fertig')->count(),
            ];

            // Stunden diese Woche
            $stundenDieseWoche = Aufgabe::where('azubi_id', $user->id)
                ->where('archiviert', false)
                ->whereIn('status', ['in_bearbeitung', 'fertig'])
                ->sum('stunden');

            // Nächste Kalender-Termine
            $naechsteTermine = KalenderEintrag::where('azubi_id', $user->id)
                ->where('datum', '>=', Carbon::today())
                ->orderBy('datum')
                ->limit(5)
                ->get();

            // Nicht signierte Berichte
            $nichtSigniert = Wochenbericht::where('azubi_id', $user->id)
                ->where('abgegeben', true)
                ->where('azubi_signiert', false)
                ->count();

            // Berichte ohne Ausbilder-Signatur
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

        return match($rolle) {
            'admin'     => view('dashboard.admin'),
            'ausbilder' => view('dashboard.ausbilder'),
            default     => abort(403)
        };
    }
}