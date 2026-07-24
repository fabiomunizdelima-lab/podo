# Migrazione da SmartPodos — stato e specifiche di export

SmartPodos è un applicativo **FileMaker Pro 12** (database `VSMP530.smp`).
Gli export si fanno **via ODBC dal lato FileMaker** e si depositano in
`storage/app/import/` sulla macchina (cartella esclusa da git).

## Già migrato

| Dato | Fonte | Comando | Esito |
|---|---|---|---|
| Fatture 2022-2025 | XML FatturaPA | `podo:import-fatturapa` | 2.603 fatture |
| Anagrafica pazienti | `pazienti.json` (ODBC) | `podo:import-pazienti` | 2.013 pazienti |
| Visite cliniche | `cliniche.json` (ODBC) | `podo:import-cliniche` | 1.532 visite + 59 ortesi |

## Da migrare — comandi pronti, servono gli export

I tre comandi seguono le convenzioni esistenti: match paziente su `legacy_fm_id`,
dedup su `legacy_ref`, opzione `--dry-run`.

### 1. Agenda storica → `podo:import-appuntamenti`

File atteso: `storage/app/import/appuntamenti.json`

```json
{
  "appointments": [
    {
      "ref": "A0001",                  // id univoco del record in FileMaker
      "fm_id": "P0123",                // N PAZIENTE (stesso di pazienti.json)
      "starts_at": "2026-07-30 09:30", // obbligatorio
      "ends_at": null,                 // opzionale: se assente, 30 minuti
      "duration_minutes": 30,          // opzionale, alternativa a ends_at
      "treatment": "Trattamento Podologico",
      "notes": "",
      "status": null                   // opzionale: se assente, passato=completed futuro=scheduled
    }
  ]
}
```

### 2. Anamnesi → `podo:import-anamnesi`

File atteso: `storage/app/import/anamnesi.json` — una scheda per paziente.

```json
{
  "records": [
    {
      "fm_id": "P0123",
      "profession": "", "sport_activity": "", "footwear_notes": "",
      "diabetes": false, "diabetes_type": "",
      "on_anticoagulants": false, "smoker": false, "hypertension": false,
      "circulatory_disorders": false, "neuropathy": false,
      "immunosuppressed": false, "pacemaker": false, "latex_allergy": false,
      "foot_type_left": "normale|piatto|cavo", "foot_type_right": "",
      "medical_history": "", "surgeries": "", "medications": "",
      "allergies": "", "podiatric_notes": ""
    }
  ]
}
```

I campi assenti o vuoti vengono ignorati (non sovrascrivono).

### 3. Foto cliniche → `podo:import-foto`

⚠️ **I file in `Files/Images/Secure` sono container FileMaker CIFRATI**: copiarli
non serve. Le immagini vanno esportate **in chiaro da FileMaker**:
- da script FileMaker: *Esporta contenuto campo* in loop sui record, oppure
- via ODBC: `SELECT GetAs(campo_contenitore, 'JPEG') ...`

Atteso in `storage/app/import/foto/`: i file immagine + `foto.json`:

```json
{
  "photos": [
    {
      "ref": "F0001",
      "fm_id": "P0123",
      "file": "F0001.jpg",            // nome del file nella stessa cartella
      "visit_ref": "V0456",           // opzionale: ref della visita collegata
      "taken_at": "2024-05-10",
      "foot": "L|R|both",
      "caption": ""
    }
  ]
}
```

All'import le immagini vengono **cifrate con la chiave dell'app** (stesso schema
delle foto caricate da interfaccia) e i sorgenti in chiaro **eliminati**
(`--keep-sources` per conservarli).

## Procedura consigliata

```bash
php artisan podo:import-appuntamenti --dry-run     # prima l'analisi
php artisan backup:run --only-db                   # backup prima di scrivere
php artisan podo:import-appuntamenti               # poi l'import vero
```

Tutti i comandi sono idempotenti: rilanciarli non crea duplicati.
