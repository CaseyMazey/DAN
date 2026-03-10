<?php

namespace App\Http\Controllers;

use App\Models\Aufgabe;
use App\Models\StundenConfig;
use App\Models\WochenberichtEintrag;
use App\Models\Wochenbericht;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AufgabeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $config = StundenConfig::firstOrCreate(
            ['azubi_id' => $user->id],
            ['max_stunden' => 42, 'betrieb_stunden' => 32, 'berufsschule_stunden' => 10]
        );

        $aufgaben = Aufgabe::where('azubi_id', $user->id)
            ->where('archiviert', false)
            ->get()
            ->groupBy('status');

        $archiviert = Aufgabe::where('azubi_id', $user->id)
            ->where('archiviert', true)
            ->orderBy('archiviert_am', 'desc')
            ->limit(20)
            ->get();

        // Gesamtstunden diese Woche berechnen
        $startDerWoche = Carbon::now()->startOfWeek()->toDateString();
        $bericht = Wochenbericht::where('azubi_id', $user->id)
            ->where('startdatum', $startDerWoche)
            ->first();

        $stundenDieseWoche = $bericht ? $bericht->gesamtstunden() : 0;
        $ueberschritten = $stundenDieseWoche > $config->max_stunden;

        return view('aufgaben.index', compact(
            'aufgaben', 'archiviert', 'config', 'stundenDieseWoche', 'ueberschritten'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titel'               => 'required|string|max:255',
            'beschreibung'        => 'nullable|string|max:2000',
            'kategorie'           => 'required|in:betrieb,berufsschule,unterweisung',
            'stunden'             => 'required|numeric|min:0.5|max:99',
            'stunden_abziehen_bei'=> 'required_if:kategorie,unterweisung|nullable|in:betrieb,berufsschule',
        ]);

        Aufgabe::create([
            'azubi_id'            => auth()->id(),
            'titel'               => $request->titel,
            'beschreibung'        => $request->beschreibung,
            'kategorie'           => $request->kategorie,
            'stunden'             => $request->stunden,
            'stunden_abziehen_bei'=> $request->stunden_abziehen_bei,
            'status'              => 'geplant',
        ]);

        return back()->with('success', 'Aufgabe erstellt.');
    }

    public function update(Request $request, $id)
    {
        $aufgabe = Aufgabe::where('azubi_id', auth()->id())->findOrFail($id);

        $request->validate([
            'titel'               => 'required|string|max:255',
            'beschreibung'        => 'nullable|string|max:2000',
            'kategorie'           => 'required|in:betrieb,berufsschule,unterweisung',
            'stunden'             => 'required|numeric|min:0.5|max:99',
            'stunden_abziehen_bei'=> 'required_if:kategorie,unterweisung|nullable|in:betrieb,berufsschule',
        ]);

        $aufgabe->update($request->only([
            'titel', 'beschreibung', 'kategorie', 'stunden', 'stunden_abziehen_bei'
        ]));

        return back()->with('success', 'Aufgabe aktualisiert.');
    }

    public function destroy($id)
    {
        $aufgabe = Aufgabe::where('azubi_id', auth()->id())->findOrFail($id);
        $aufgabe->delete();

        return back()->with('success', 'Aufgabe gelöscht.');
    }

    public function statusAendern(Request $request, $id)
    {
        $aufgabe = Aufgabe::where('azubi_id', auth()->id())->findOrFail($id);

        $request->validate([
            'status' => 'required|in:geplant,in_bearbeitung,fertig',
        ]);

        $aufgabe->update(['status' => $request->status]);

        // Wenn fertig → in Wochenbericht übernehmen und archivieren
        if ($request->status === 'fertig') {
            $this->inWochenberichtUebernehmen($aufgabe);
        }

        return back()->with('success', 'Status geändert.');
    }

    public function archivieren($id)
    {
        $aufgabe = Aufgabe::where('azubi_id', auth()->id())->findOrFail($id);

        $aufgabe->update([
            'archiviert'    => true,
            'archiviert_am' => now(),
        ]);

        return back()->with('success', 'Aufgabe archiviert.');
    }

    public function wiederherstellen($id)
    {
        $aufgabe = Aufgabe::where('azubi_id', auth()->id())->findOrFail($id);

        $aufgabe->update([
            'archiviert'    => false,
            'archiviert_am' => null,
        ]);

        return back()->with('success', 'Aufgabe wiederhergestellt.');
    }

    public function stundenConfigUpdate(Request $request)
    {
        $request->validate([
            'max_stunden'          => 'required|numeric|min:1|max:80',
            'betrieb_stunden'      => 'required|numeric|min:0|max:80',
            'berufsschule_stunden' => 'required|numeric|min:0|max:80',
        ]);

        StundenConfig::updateOrCreate(
            ['azubi_id' => auth()->id()],
            [
                'max_stunden'          => $request->max_stunden,
                'betrieb_stunden'      => $request->betrieb_stunden,
                'berufsschule_stunden' => $request->berufsschule_stunden,
            ]
        );

        return back()->with('success', 'Stunden-Konfiguration gespeichert.');
    }

    private function inWochenberichtUebernehmen(Aufgabe $aufgabe)
    {
        if ($aufgabe->in_wochenbericht) return;

        $start = Carbon::now()->startOfWeek()->toDateString();
        $end   = Carbon::now()->endOfWeek()->subDays(2)->toDateString();

        $bericht = Wochenbericht::firstOrCreate(
            ['azubi_id' => $aufgabe->azubi_id, 'startdatum' => $start],
            ['enddatum' => $end]
        );

        // Kategorie bestimmen
        $kategorie = $aufgabe->kategorie;
        if ($aufgabe->kategorie === 'unterweisung') {
            $kategorie = $aufgabe->stunden_abziehen_bei ?? 'betrieb';
        }

        // Unterweisung bekommt eigene Kategorie
        if ($aufgabe->kategorie === 'unterweisung') {
            $kategorie = 'unterweisung';
        }

        WochenberichtEintrag::create([
            'wochenbericht_id' => $bericht->id,
            'kategorie'        => $kategorie,
            'beschreibung'     => $aufgabe->titel . ($aufgabe->beschreibung ? ': ' . $aufgabe->beschreibung : ''),
            'stunden'          => $aufgabe->stunden,
        ]);

        $aufgabe->update([
            'in_wochenbericht' => true,
            'archiviert'       => true,
            'archiviert_am'    => now(),
        ]);
    }
}