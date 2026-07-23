<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gestione del listino prestazioni.
 * La consultazione (index) e aperta a tutti i ruoli operativi;
 * la modifica del listino e riservata ad admin/superadmin (vedi routes).
 */
class TreatmentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input("q"));
        $category = trim((string) $request->input("category"));

        $treatments = Treatment::query()
            ->when($q !== "", function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where("name", "ilike", "%{$q}%")
                        ->orWhere("code", "ilike", "%{$q}%");
                });
            })
            ->when($category !== "", fn ($query) => $query->where("category", $category))
            ->orderBy("category")
            ->orderBy("name")
            ->paginate(25)
            ->withQueryString();

        $categories = Treatment::query()
            ->whereNotNull("category")
            ->distinct()
            ->orderBy("category")
            ->pluck("category");

        return view("treatments.index", compact("treatments", "categories", "q", "category"));
    }

    public function create()
    {
        return view("treatments.create", ["treatment" => new Treatment()]);
    }

    public function store(Request $request)
    {
        Treatment::create($this->validateData($request));

        return redirect()->route("treatments.index")
            ->with("success", "Prestazione aggiunta al listino.");
    }

    public function edit(Treatment $treatment)
    {
        return view("treatments.edit", compact("treatment"));
    }

    public function update(Request $request, Treatment $treatment)
    {
        $treatment->update($this->validateData($request, $treatment));

        return redirect()->route("treatments.index")
            ->with("success", "Prestazione aggiornata.");
    }

    public function destroy(Treatment $treatment)
    {
        $treatment->delete();

        return redirect()->route("treatments.index")
            ->with("success", "Prestazione archiviata.");
    }

    private function validateData(Request $request, ?Treatment $treatment = null): array
    {
        $data = $request->validate([
            "code" => ["nullable", "string", "max:30", Rule::unique("treatments", "code")->ignore($treatment?->id)->whereNull("deleted_at")],
            "name" => ["required", "string", "max:150"],
            "category" => ["nullable", "string", "max:100"],
            "description" => ["nullable", "string", "max:2000"],
            "price" => ["required", "numeric", "min:0", "max:99999.99"],
            "vat_exempt" => ["sometimes", "boolean"],
            "vat_rate" => ["nullable", "numeric", "min:0", "max:100"],
            "vat_nature" => ["nullable", "string", "max:4"],
            "ts_type" => ["nullable", "string", "max:8"],
            "duration_minutes" => ["nullable", "integer", "min:0", "max:600"],
            "is_active" => ["sometimes", "boolean"],
        ]);

        $data["vat_exempt"] = $request->boolean("vat_exempt");
        $data["is_active"] = $request->boolean("is_active");
        $data["vat_rate"] = $data["vat_exempt"] ? 0 : ($data["vat_rate"] ?? 0);

        return $data;
    }
}
