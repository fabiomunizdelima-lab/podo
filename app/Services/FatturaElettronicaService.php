<?php

namespace App\Services;
use App\Models\Setting;

use App\Models\Invoice;
use DOMDocument;
use DOMElement;

/**
 * Generatore del file XML FatturaPA (formato SDI FatturaElettronica v1.2, tipo FPR12 privati).
 *
 * NOTA: i dati del cedente/prestatore vanno compilati in .env (sezione billing).
 * L XML prodotto e strutturalmente conforme; prima dell invio reale allo SDI
 * va validato con lo schema ufficiale e i dati fiscali reali dello studio.
 */
class FatturaElettronicaService
{
    public function build(Invoice $invoice): string
    {
        $cfg = Setting::billing();
        $invoice->loadMissing('lines');

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('p:FatturaElettronica');
        $root->setAttribute('versione', 'FPR12');
        $root->setAttribute('xmlns:p', 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $dom->appendChild($root);

        // ---------- Header ----------
        $header = $dom->createElement('FatturaElettronicaHeader');
        $root->appendChild($header);

        $dt = $dom->createElement('DatiTrasmissione');
        $header->appendChild($dt);
        $idTx = $this->child($dom, $dt, 'IdTrasmittente');
        $this->text($dom, $idTx, 'IdPaese', 'IT');
        $this->text($dom, $idTx, 'IdCodice', $cfg['vat_number'] ?: '00000000000');
        $this->text($dom, $dt, 'ProgressivoInvio', (string) ($invoice->number ?: $invoice->id));
        $this->text($dom, $dt, 'FormatoTrasmissione', 'FPR12');
        $sdi = $cfg['sdi_code'] ?: '0000000';
        $this->text($dom, $dt, 'CodiceDestinatario', str_pad($sdi, 7, '0'));
        if ($sdi === '0000000' && $cfg['pec']) {
            $this->text($dom, $dt, 'PECDestinatario', $cfg['pec']);
        }

        // Cedente / prestatore (lo studio)
        $ced = $dom->createElement('CedentePrestatore');
        $header->appendChild($ced);
        $cedAna = $this->child($dom, $ced, 'DatiAnagrafici');
        if ($cfg['vat_number']) {
            $idIva = $this->child($dom, $cedAna, 'IdFiscaleIVA');
            $this->text($dom, $idIva, 'IdPaese', 'IT');
            $this->text($dom, $idIva, 'IdCodice', $cfg['vat_number']);
        }
        if ($cfg['fiscal_code']) {
            $this->text($dom, $cedAna, 'CodiceFiscale', $cfg['fiscal_code']);
        }
        $cedNome = $this->child($dom, $cedAna, 'Anagrafica');
        $this->text($dom, $cedNome, 'Denominazione', $cfg['studio_name']);
        $this->text($dom, $cedAna, 'RegimeFiscale', $cfg['tax_regime_code'] ?: 'RF19');
        $cedSede = $this->child($dom, $ced, 'Sede');
        $this->text($dom, $cedSede, 'Indirizzo', $cfg['address'] ?: '-');
        $this->text($dom, $cedSede, 'CAP', $cfg['cap'] ?: '00000');
        $this->text($dom, $cedSede, 'Comune', $cfg['city'] ?: '-');
        if ($cfg['province']) {
            $this->text($dom, $cedSede, 'Provincia', $cfg['province']);
        }
        $this->text($dom, $cedSede, 'Nazione', $cfg['country'] ?: 'IT');

        // Cessionario / committente (il paziente)
        $ces = $dom->createElement('CessionarioCommittente');
        $header->appendChild($ces);
        $cesAna = $this->child($dom, $ces, 'DatiAnagrafici');
        if ($invoice->client_fiscal_code) {
            $this->text($dom, $cesAna, 'CodiceFiscale', $invoice->client_fiscal_code);
        }
        $cesNome = $this->child($dom, $cesAna, 'Anagrafica');
        $this->text($dom, $cesNome, 'Denominazione', $invoice->client_name);
        $cesSede = $this->child($dom, $ces, 'Sede');
        $this->text($dom, $cesSede, 'Indirizzo', $invoice->client_address ?: '-');
        $this->text($dom, $cesSede, 'CAP', $invoice->client_cap ?: '00000');
        $this->text($dom, $cesSede, 'Comune', $invoice->client_city ?: '-');
        if ($invoice->client_province) {
            $this->text($dom, $cesSede, 'Provincia', $invoice->client_province);
        }
        $this->text($dom, $cesSede, 'Nazione', 'IT');

        // ---------- Body ----------
        $body = $dom->createElement('FatturaElettronicaBody');
        $root->appendChild($body);

        $dg = $this->child($dom, $body, 'DatiGenerali');
        $dgd = $this->child($dom, $dg, 'DatiGeneraliDocumento');
        $this->text($dom, $dgd, 'TipoDocumento', 'TD01');
        $this->text($dom, $dgd, 'Divisa', $cfg['currency'] ?: 'EUR');
        $this->text($dom, $dgd, 'Data', optional($invoice->issued_at)->format('Y-m-d') ?: now()->format('Y-m-d'));
        $this->text($dom, $dgd, 'Numero', (string) $invoice->full_number);
        if ((float) $invoice->stamp_amount > 0) {
            $bollo = $this->child($dom, $dgd, 'DatiBollo');
            $this->text($dom, $bollo, 'BolloVirtuale', 'SI');
            $this->text($dom, $bollo, 'ImportoBollo', $this->num($invoice->stamp_amount));
        }
        $this->text($dom, $dgd, 'ImportoTotaleDocumento', $this->num($invoice->total));

        $dbs = $this->child($dom, $body, 'DatiBeniServizi');
        $n = 1;
        foreach ($invoice->lines as $line) {
            $dl = $this->child($dom, $dbs, 'DettaglioLinee');
            $this->text($dom, $dl, 'NumeroLinea', (string) $n++);
            $this->text($dom, $dl, 'Descrizione', $line->description);
            $this->text($dom, $dl, 'Quantita', $this->num($line->quantity, 2));
            $this->text($dom, $dl, 'PrezzoUnitario', $this->num($line->unit_price));
            $this->text($dom, $dl, 'PrezzoTotale', $this->num($line->line_total));
            $this->text($dom, $dl, 'AliquotaIVA', $this->num($line->vat_rate));
            if ($line->vat_nature) {
                $this->text($dom, $dl, 'Natura', $line->vat_nature);
            }
        }

        // Riepilogo per aliquota/natura (raggruppamento semplice esente vs imponibile)
        $groups = [];
        foreach ($invoice->lines as $line) {
            $key = $line->vat_rate.'|'.($line->vat_nature ?? '');
            $groups[$key] ??= ['rate' => $line->vat_rate, 'nature' => $line->vat_nature, 'taxable' => 0];
            $groups[$key]['taxable'] += (float) $line->line_total;
        }
        foreach ($groups as $g) {
            $dr = $this->child($dom, $dbs, 'DatiRiepilogo');
            $this->text($dom, $dr, 'AliquotaIVA', $this->num($g['rate']));
            if ($g['nature']) {
                $this->text($dom, $dr, 'Natura', $g['nature']);
            }
            $this->text($dom, $dr, 'ImponibileImporto', $this->num($g['taxable']));
            $imposta = round($g['taxable'] * ($g['rate'] / 100), 2);
            $this->text($dom, $dr, 'Imposta', $this->num($imposta));
            if ($g['nature']) {
                $this->text($dom, $dr, 'RiferimentoNormativo', $cfg['register_note']);
            }
        }

        return $dom->saveXML();
    }

    public function filename(Invoice $invoice): string
    {
        $cfg = Setting::billing();
        $id = $cfg['vat_number'] ?: '00000000000';
        $prog = str_pad((string) ($invoice->number ?: $invoice->id), 5, '0', STR_PAD_LEFT);

        return 'IT'.$id.'_'.$prog.'.xml';
    }

    private function child(DOMDocument $dom, DOMElement $parent, string $name): DOMElement
    {
        $el = $dom->createElement($name);
        $parent->appendChild($el);

        return $el;
    }

    private function text(DOMDocument $dom, DOMElement $parent, string $name, string $value): void
    {
        $el = $dom->createElement($name);
        $el->appendChild($dom->createTextNode($value));
        $parent->appendChild($el);
    }

    private function num($value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, '.', '');
    }
}
