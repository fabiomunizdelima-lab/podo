<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anamnesi unica del paziente (1:1). Dati sanitari (art.9 GDPR):
 * i campi di testo clinici sono cifrati a riposo dal model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create("clinical_records", function (Blueprint $table) {
            $table->id();
            $table->foreignId("patient_id")->unique()->constrained()->cascadeOnDelete();

            $table->string("profession")->nullable();
            $table->string("sport_activity")->nullable();
            $table->text("footwear_notes")->nullable();

            // Fattori di rischio (flag rapidi per il podologo)
            $table->boolean("diabetes")->default(false);
            $table->string("diabetes_type", 20)->nullable();
            $table->boolean("on_anticoagulants")->default(false);
            $table->boolean("smoker")->default(false);
            $table->boolean("hypertension")->default(false);
            $table->boolean("circulatory_disorders")->default(false);
            $table->boolean("neuropathy")->default(false);
            $table->boolean("immunosuppressed")->default(false);
            $table->boolean("pacemaker")->default(false);
            $table->boolean("latex_allergy")->default(false);

            // Morfologia del piede
            $table->string("foot_type_left", 20)->nullable();   // normale/piatto/cavo
            $table->string("foot_type_right", 20)->nullable();

            // Testo clinico cifrato
            $table->text("medical_history")->nullable();
            $table->text("surgeries")->nullable();
            $table->text("medications")->nullable();
            $table->text("allergies")->nullable();
            $table->text("podiatric_notes")->nullable();

            $table->foreignId("updated_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("clinical_records");
    }
};
