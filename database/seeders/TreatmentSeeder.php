<?php

namespace Database\Seeders;

use App\Models\Treatment;
use Illuminate\Database\Seeder;

/**
 * Listino prestazioni podologiche di partenza.
 * Prezzi indicativi: vanno adeguati dal titolare dello studio.
 * Prestazioni sanitarie esenti IVA (art.10 c.1 n.18) -> natura N4.
 */
class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ["PV01", "Prima visita podologica", "Visite", 60.00, 40],
            ["PV02", "Visita di controllo", "Visite", 35.00, 20],
            ["TP01", "Trattamento podologico completo", "Podologia generale", 45.00, 40],
            ["TP02", "Rimozione callosita e ipercheratosi", "Podologia generale", 40.00, 30],
            ["TP03", "Trattamento tiloma (occhio di pernice)", "Podologia generale", 40.00, 30],
            ["ON01", "Trattamento onicomicosi", "Onicologia", 45.00, 30],
            ["ON02", "Onicectomia parziale (unghia incarnita)", "Onicologia", 80.00, 45],
            ["ON03", "Trattamento onicocriptosi con ortesi ungueale", "Onicologia", 70.00, 45],
            ["VP01", "Trattamento verruca plantare", "Podologia generale", 50.00, 30],
            ["OR01", "Ortesi plantare su misura (paio)", "Ortesi", 180.00, 60],
            ["OR02", "Ortesi digitale in silicone", "Ortesi", 60.00, 30],
            ["OR03", "Ortesi ungueale correttiva", "Ortesi", 70.00, 30],
            ["PD01", "Trattamento del piede diabetico", "Piede diabetico", 55.00, 45],
            ["PD02", "Medicazione avanzata lesione", "Piede diabetico", 40.00, 30],
            ["BF01", "Bendaggio funzionale", "Podologia generale", 30.00, 20],
        ];

        foreach ($items as [$code, $name, $category, $price, $minutes]) {
            Treatment::firstOrCreate(
                ["code" => $code],
                [
                    "name" => $name,
                    "category" => $category,
                    "price" => $price,
                    "duration_minutes" => $minutes,
                    "vat_exempt" => true,
                    "vat_nature" => "N4",
                    "is_active" => true,
                ]
            );
        }

        $this->command->info("Listino prestazioni: ".count($items)." voci di base inserite.");
    }
}
