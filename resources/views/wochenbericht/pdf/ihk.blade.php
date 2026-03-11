<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            padding: 20mm 15mm;
            color: #000;
        }

        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .ihk-logo {
            font-size: 22px;
            font-weight: bold;
            border: 3px solid #000;
            padding: 4px 10px;
            margin-right: 20px;
            color: #003399;
        }
        .ihk-logo span {
            font-size: 10px;
            display: block;
            color: #000;
            font-weight: normal;
        }

        hr.thick { border: 1.5px solid #000; margin: 8px 0 12px 0; }
        hr.thin  { border: 0.5px solid #000; margin: 6px 0; }

        h1 { font-size: 16px; font-weight: bold; margin-bottom: 12px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table td { border: 1px solid #000; padding: 4px 6px; }
        table td.label { width: 35%; background: #fff; }
        table td.value { width: 65%; }
        table td.label-sm { width: 35%; }
        table td.value-sm { width: 15%; }
        table td.label2 { width: 20%; }
        table td.value2 { width: 30%; }

        .section-header {
            background: #d0d0d0;
            font-weight: bold;
            padding: 5px 6px;
            border: 1px solid #000;
            display: flex;
            justify-content: space-between;
        }
        .section-header span { font-style: italic; }

        .section-content {
            border: 1px solid #000;
            border-top: none;
            min-height: 80px;
            padding: 6px;
        }

        .section-content p {
            margin-bottom: 3px;
            line-height: 1.4;
        }

        .signature-text {
            font-size: 10px;
            margin: 12px 0 20px 0;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .sig-block {
            width: 45%;
        }
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-size: 10px;
        }

        .footer {
            border-top: 1px solid #000;
            margin-top: 20px;
            padding-top: 4px;
            text-align: center;
            font-size: 9px;
        }
    </style>
</head>
<body>

    {{-- Logo & Trennlinie --}}
    <div class="header">
        <div class="ihk-logo">
            🦅 <strong>IHK</strong>
        </div>
    </div>
    <hr class="thick">

    <h1>Ausbildungsnachweis (wöchentlich)</h1>

    {{-- Kopfdaten --}}
    <table>
        <tr>
            <td class="label">Name des/der Auszubildenden:</td>
            <td class="value">{{ $azubi->full_name }}</td>
        </tr>
        <tr>
            <td class="label-sm">Ausbildungsjahr:</td>
            <td class="value-sm">{{ $ausbildungsjahr }}</td>
            <td class="label2">Ggf. ausbildende Abteilung:</td>
            <td class="value2">{{ $azubi->abteilung ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Ausbildungswoche vom:</td>
            <td class="value">
                {{ $bericht->startdatum->format('d.m.Y') }} &nbsp;&nbsp; bis: &nbsp;&nbsp; {{ $bericht->enddatum->format('d.m.Y') }}
            </td>
        </tr>
    </table>

    {{-- Betriebliche Tätigkeiten --}}
    <div class="section-header">
        <span style="font-style:normal; font-weight:bold;">Betriebliche Tätigkeiten</span>
        <span>{{ number_format($stunden['betrieb'], 2) }} Stunden</span>
    </div>
    <div class="section-content">
        @forelse($eintraege['betrieb'] as $e)
            <p>{{ $e->beschreibung }}</p>
        @empty
            <p style="color:#999;">Keine Einträge.</p>
        @endforelse
    </div>

    {{-- Unterweisungen --}}
    <div class="section-header" style="margin-top:8px;">
        <span style="font-style:normal; font-weight:bold;">Unterweisungen bzw. überbetriebliche Unterweisungen, betrieblicher Unterricht, sonstige Schulungen</span>
        <span>{{ number_format($stunden['unterweisung'], 2) }} Stunden</span>
    </div>
    <div class="section-content">
        @forelse($eintraege['unterweisung'] as $e)
            <p>{{ $e->beschreibung }}</p>
        @empty
            <p style="color:#999;">Keine Einträge.</p>
        @endforelse
    </div>

    {{-- Berufsschule --}}
    <div class="section-header" style="margin-top:8px;">
        <span style="font-style:normal; font-weight:bold;">Themen des Berufsschulunterrichts</span>
        <span>{{ number_format($stunden['berufsschule'], 2) }} Stunden</span>
    </div>
    <div class="section-content">
        @forelse($eintraege['berufsschule'] as $e)
            <p>{{ $e->beschreibung }}</p>
        @empty
            <p style="color:#999;">Keine Einträge.</p>
        @endforelse
    </div>

    {{-- Unterschrift --}}
    <p class="signature-text">
        Durch die nachfolgende Unterschrift wird die Richtigkeit und Vollständigkeit der obigen Angaben bestätigt.
    </p>

    <div class="signatures">
        <div class="sig-block">
            <div style="height: 30px;"></div>
            <div class="sig-line">
                Datum, Unterschrift Auszubildende/r
                @if($bericht->azubi_signiert)
                    <br><small>Signiert am {{ $bericht->azubi_signiert_am?->format('d.m.Y') }}</small>
                @endif
            </div>
        </div>
        <div class="sig-block">
            <div style="height: 30px;"></div>
            <div class="sig-line">
                Datum, Unterschrift Ausbildende/r oder Ausbilder/in
                @if($bericht->signiert)
                    <br><small>Signiert am {{ $bericht->signiert_am?->format('d.m.Y') }}</small>
                @endif
            </div>
        </div>
    </div>

    <div class="footer">Seite 1 von 1</div>

</body>
</html>