<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prestazioni erogate durante una visita (righe da listino).
 * Salva uno snapshot di descrizione e prezzo: alimenta la fatturazione.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create("clinical_visit_treatment", function (Blueprint $table) {
            $table->id();
            $table->foreignId("clinical_visit_id")->constrained()->cascadeOnDelete();
            $table->foreignId("treatment_id")->nullable()->constrained()->nullOnDelete();
            $table->string("description");
            $table->unsignedSmallInteger("quantity")->default(1);
            $table->decimal("unit_price", 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("clinical_visit_treatment");
    }
};
