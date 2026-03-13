<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use App\Models\Aufgabe;

class WochenberichtPdfService
{
    public function generate($bericht, $azubi, $ausbildungsjahr, $eintraege, $stunden)
    {
        $pdf = new Fpdi();
        $pdf->AddPage('P', 'A4');
        $pdf->setSourceFile(storage_path('app/vorlage_wochenbericht.pdf'));
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, 210, 297);

        $pdf->SetFont('Helvetica', '', 10);

        // Kopfdaten
        $pdf->SetXY(85, 49);
        $pdf->Write(0, $azubi->full_name);

        $pdf->SetXY(85, 58);
        $pdf->Write(0, $ausbildungsjahr . '. Jahr');

        $pdf->SetXY(151, 55);
        $pdf->MultiCell(55, 4, $this->wrapText($azubi->abteilung ?? '', 22), 0, 'L');

        $pdf->SetXY(85, 67);
        $pdf->Write(0, $bericht->startdatum->format('d.m.Y'));

        $pdf->SetXY(116, 67);
        $pdf->Write(0, $bericht->enddatum->format('d.m.Y'));

        // Sektionen ausgeben
        $sektionen = [
            'betrieb'      => ['stundenY' => 90,  'inhaltY' => 88,  'maxY' => 128],
            'unterweisung' => ['stundenY' => 141, 'inhaltY' => 139, 'maxY' => 188],
            'berufsschule' => ['stundenY' => 191, 'inhaltY' => 189, 'maxY' => 248],
        ];

        foreach ($sektionen as $key => $pos) {
            // Stunden
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetXY(180, $pos['stundenY']);
            $pdf->Write(0, number_format($stunden[$key], 2));

            // Inhalt
            $pdf->SetFont('Helvetica', '', 9);
            $y = $pos['inhaltY'];
            foreach ($eintraege[$key] as $e) {
                $text = $e instanceof Aufgabe ? $e->titel : $e->beschreibung;
                $pdf->SetXY(27, $y);
                $pdf->MultiCell(150, 5, $text, 0, 'L');
                $y += 6;
                if ($y > $pos['maxY']) break;
            }
        }

        // Azubi Signatur
        if ($bericht->azubi_signiert && $azubi->signatur_pfad) {
            $pfad = storage_path('app/public/' . $azubi->signatur_pfad);
            if (file_exists($pfad)) {
                $pdf->Image($pfad, 27, 236, 60, 20, 'PNG');
            }
        }

        // Ausbilder Signatur
        if ($bericht->signiert) {
            // Ausbilder-Objekt brauchen wir noch — kommt später
        }

        return $pdf->Output('S');
    }

    private function wrapText(string $text, int $maxChars): string
    {
        $lines = [];
        $words = explode(' ', $text);
        $zeile = '';

        foreach ($words as $word) {
            if ($zeile === '') {
                $zeile = $word;
            } elseif (strlen($zeile . ' ' . $word) <= $maxChars) {
                $zeile .= ' ' . $word;
            } else {
                $lines[] = $zeile;
                $zeile = $word;
            }
        }

        if ($zeile !== '') {
            $lines[] = $zeile;
        }

        return implode("\n", $lines);
    }
}