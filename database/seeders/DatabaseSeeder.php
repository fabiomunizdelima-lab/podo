<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crea i tre utenti base. Le password iniziali vengono generate
        // casualmente e stampate in console: vanno cambiate al primo accesso.
        $accounts = [
            ["name" => "Super Admin", "email" => "superadmin@podo.local", "role" => Role::SUPERADMIN],
            ["name" => "Amministratore", "email" => "admin@podo.local", "role" => Role::ADMIN],
            ["name" => "Utente", "email" => "utente@podo.local", "role" => Role::USER],
        ];

        foreach ($accounts as $acc) {
            $exists = User::where("email", $acc["email"])->exists();
            if ($exists) {
                continue;
            }

            // Password robusta iniziale (override con env per ambienti CI/demo)
            $password = env("SEED_".strtoupper($acc["role"]->value)."_PASSWORD")
                ?: Str::password(16);

            User::create([
                "name" => $acc["name"],
                "email" => $acc["email"],
                "password" => Hash::make($password),
                "role" => $acc["role"]->value,
                "is_active" => true,
            ]);

            $this->command->warn(sprintf(
                "Utente %-12s  %-22s  password: %s",
                $acc["role"]->value,
                $acc["email"],
                $password
            ));
        }

        // Listino prestazioni podologiche di base
        $this->call(TreatmentSeeder::class);

        $this->command->info("IMPORTANTE: cambia le password al primo accesso. Gli admin dovranno attivare la MFA.");
    }
}
