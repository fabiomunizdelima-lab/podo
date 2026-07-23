<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Non tutte le fatture sono intestate a un paziente: lo studio emette anche
 * fatture di collaborazione verso strutture (con denominazione e P.IVA).
 * I dati del cliente restano nei campi snapshot della fattura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable(false)->change();
        });
    }
};
