<?php

namespace App\Http\Controllers;

use App\Models\KalenderEintrag;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Fach;

class KalenderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $faecher = Fach::where('azubi_id', $user->id)->orderBy('name')->get();

        // Kommende Einträge
        $kommend = KalenderEintrag::where('azubi_id', $user->id)
            ->where('datum', '>=', Carbon::today())
            ->orderBy('datum')
            ->get();

        // Vergangene Einträge mit Noten
        $vergangen = KalenderEintrag::where('azubi_id', $user->id)
            ->where('datum', '<', Carbon::today())
            ->orderBy('datum', 'desc')
            ->get();

        // Notenübersicht nach Fach
        $noten = KalenderEintrag::where('azubi_id', $user->id)
            ->whereNotNull('note')
            ->get()
            ->groupBy('fach')
            ->map(function ($eintraege) {
                return [
                    'eintraege'   => $eintraege,
                    'durchschnitt'=> round($eintraege->avg('note'), 2),
                ];
            });

        $gesamtdurchschnitt = KalenderEintrag::where('azubi_id', $user->id)
            ->whereNotNull('note')
            ->avg('note');

        $gesamtdurchschnitt = $gesamtdurchschnitt ? round($gesamtdurchschnitt, 2) : null;

        return view('kalender.index', compact('kommend', 'vergangen', 'noten', 'gesamtdurchschnitt', 'faecher'));
        }

    public function store(Request $request)
    {
        $request->validate([
            'fach'        => 'required|string|max:255',
            'typ'         => 'required|in:test,klassenarbeit,hausaufgabe,projekt,sonstiges',
            'datum'       => 'required|date',
            'thema'       => 'nullable|string|max:255',
            'lernnotizen' => 'nullable|string|max:2000',
            'note'        => 'nullable|numeric|min:1|max:6',
        ]);

        KalenderEintrag::create([
            'azubi_id'    => auth()->id(),
            'fach'        => $request->fach,
            'typ'         => $request->typ,
            'datum'       => $request->datum,
            'thema'       => $request->thema,
            'lernnotizen' => $request->lernnotizen,
            'note'        => $request->note,
        ]);

        return back()->with('success', 'Eintrag erstellt.');
    }

    public function update(Request $request, $id)
    {
        $eintrag = KalenderEintrag::where('azubi_id', auth()->id())->findOrFail($id);

        $request->validate([
            'fach'        => 'required|string|max:255',
            'typ'         => 'required|in:test,klassenarbeit,hausaufgabe,projekt,sonstiges',
            'datum'       => 'required|date',
            'thema'       => 'nullable|string|max:255',
            'lernnotizen' => 'nullable|string|max:2000',
            'note'        => 'nullable|numeric|min:1|max:6',
        ]);

        $eintrag->update($request->only([
            'fach', 'typ', 'datum', 'thema', 'lernnotizen', 'note'
        ]));

        return back()->with('success', 'Eintrag aktualisiert.');
    }

        public function destroy($id)
        {
            $eintrag = KalenderEintrag::where('azubi_id', auth()->id())->findOrFail($id);
            $eintrag->delete();

            return back()->with('success', 'Eintrag gelöscht.');
        }
        public function fachErstellen(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $fach = Fach::firstOrCreate([
                'azubi_id' => auth()->id(),
                'name'     => $request->name,
            ]);

            return response()->json(['success' => true, 'id' => $fach->id]);
        }

    public function fachLoeschen($id)
    {
        $fach = Fach::where('azubi_id', auth()->id())->findOrFail($id);
        $fach->delete();

        return back()->with('success', 'Fach gelöscht.');
    }
}