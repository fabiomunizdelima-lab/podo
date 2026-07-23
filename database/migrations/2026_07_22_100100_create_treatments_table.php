<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listino prestazioni podologiche.
 * Catalogo dei trattamenti erogabili, con prezzo e dati fiscali
 * (IVA / natura FatturaPA, tipologia spesa Sistema TS) usati poi
 * da cartella clinica e fatturazione.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create("treatments", function (Blueprint $table) {
            $table->id();
            $table->string("code", 30)->nullable()->index();
            $table->string("name");
            $table->string("category")->nullable()->index();
            $table->text("description")->nullable();

            $table->decimal("price", 10, 2)->default(0);
            // Prestazioni sanitarie rese da podologo iscritto all albo:
            // esenti IVA art.10 c.1 n.18 DPR 633/72 -> natura FatturaPA N4.
            $table->boolean("vat_exempt")->default(true);
            $table->decimal("vat_rate", 5, 2)->default(0);
            $table->string("vat_nature", 4)->nullable()->default("N4");
            $table->string("ts_type", 8)->nullable();

            $table->unsignedSmallInteger("duration_minutes")->nullable();
            $table->boolean("is_active")->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(["name"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("treatments");
    }
};
