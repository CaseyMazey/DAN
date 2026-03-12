<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;

class WochenberichtPdfService
{
    public function generate($bericht, $azubi, $ausbildungsjahr)
    {
        $pdf = new Fpdi();
        $pdf->AddPage('P', 'A4');
        $pdf->setSourceFile(storage_path('app/vorlage_wochenbericht.pdf'));
        $tplId = $pdf->importPage(1);
        $pdf->useTemplate($tplId, 0, 0, 210, 297);

        $pdf->SetFont('Helvetica', '', 10);

        // Name
        $pdf->SetXY(85, 49);
        $pdf->Write(0, $azubi->full_name);

        // Ausbildungsjahr
        $pdf->SetXY(85, 58);
        $pdf->Write(0, $ausbildungsjahr . '. Jahr');

        // Abteilung
        $pdf->SetXY(165, 59);
        $pdf->Write(0, $azubi->abteilung ?? '');

        // Datum von
        $pdf->SetXY(85, 67);
        $pdf->Write(0, $bericht->startdatum->format('d.m.Y'));

        // Datum bis
        $pdf->SetXY(116, 67);
        $pdf->Write(0, $bericht->enddatum->format('d.m.Y'));

        // Betrieb Stunden
        $betriebEintraege = $bericht->eintraege->where('kategorie', 'betrieb');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(180, 90);
        $pdf->Write(0, number_format($betriebEintraege->sum('stunden'), 2));

        // Betrieb Inhalt
        $pdf->SetFont('Helvetica', '', 9);
        $y = 96;
        foreach ($betriebEintraege as $e) {
            $pdf->SetXY(15, $y);
            $pdf->MultiCell(160, 5, $e->beschreibung, 0, 'L');
            $y += 6;
            if ($y > 128) break;
        }

        // Unterweisung Stunden
        $unterweisungEintraege = $bericht->eintraege->where('kategorie', 'unterweisung');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(180, 141);
        $pdf->Write(0, number_format($unterweisungEintraege->sum('stunden'), 2));

        // Unterweisung Inhalt
        $pdf->SetFont('Helvetica', '', 9);
        $y = 156;
        foreach ($unterweisungEintraege as $e) {
            $pdf->SetXY(15, $y);
            $pdf->MultiCell(160, 5, $e->beschreibung, 0, 'L');
            $y += 6;
            if ($y > 188) break;
        }

        // Berufsschule Stunden
        $berufsschuleEintraege = $bericht->eintraege->where('kategorie', 'berufsschule');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(180, 191);
        $pdf->Write(0, number_format($berufsschuleEintraege->sum('stunden'), 2));

        // Berufsschule Inhalt
        $pdf->SetFont('Helvetica', '', 9);
        $y = 216;
        foreach ($berufsschuleEintraege as $e) {
            $pdf->SetXY(15, $y);
            $pdf->MultiCell(160, 5, $e->beschreibung, 0, 'L');
            $y += 6;
            if ($y > 248) break;
        }

        // Signaturdaten
        if ($bericht->azubi_signiert) {
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(15, 272);
            $pdf->Write(0, $bericht->azubi_signiert_am?->format('d.m.Y') . ', ' . $azubi->full_name);
        }

        if ($bericht->signiert) {
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(115, 272);
            $pdf->Write(0, $bericht->signiert_am?->format('d.m.Y'));
        }

        return $pdf->Output('S');
    }
}