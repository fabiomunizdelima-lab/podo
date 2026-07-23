<?php

namespace App\Http\Controllers;

use App\Models\ClinicalPhoto;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documentazione fotografica clinica.
 * I file sono cifrati (AES-256) e salvati su disco privato locale,
 * mai serviti staticamente: passano sempre da questo controller autenticato.
 */
class ClinicalPhotoController extends Controller
{
    private const DISK = 'local';

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'clinical_visit_id' => ['nullable', 'integer', 'exists:clinical_visits,id'],
            'foot' => ['nullable', 'in:L,R,both'],
            'caption' => ['nullable', 'string', 'max:200'],
            'taken_at' => ['nullable', 'date'],
        ]);

        $file = $request->file('photo');
        $relPath = 'clinical/'.$patient->id.'/'.Str::uuid()->toString().'.enc';

        // Cifra il contenuto binario prima di scriverlo su disco.
        $encrypted = Crypt::encryptString(base64_encode(file_get_contents($file->getRealPath())));
        Storage::disk(self::DISK)->put($relPath, $encrypted);

        $patient->clinicalPhotos()->create([
            'clinical_visit_id' => $data['clinical_visit_id'] ?? null,
            'disk' => self::DISK,
            'path' => $relPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'foot' => $data['foot'] ?? null,
            'caption' => $data['caption'] ?? null,
            'taken_at' => $data['taken_at'] ?? now(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Foto caricata.');
    }

    public function show(ClinicalPhoto $photo): Response
    {
        abort_unless(Storage::disk($photo->disk)->exists($photo->path), 404);

        $binary = base64_decode(Crypt::decryptString(Storage::disk($photo->disk)->get($photo->path)));

        return response($binary, 200, [
            'Content-Type' => $photo->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($photo->original_name ?: 'foto').'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(ClinicalPhoto $photo)
    {
        $patient = $photo->patient;
        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();

        return back()->with('success', 'Foto eliminata.');
    }
}
