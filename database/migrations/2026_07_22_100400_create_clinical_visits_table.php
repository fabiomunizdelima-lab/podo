<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visita / trattamento clinico datato. Molte per paziente.
 * Puo essere collegata a un appuntamento in agenda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create("clinical_visits", function (Blueprint $table) {
            $table->id();
            $table->foreignId("patient_id")->constrained()->cascadeOnDelete();
            $table->foreignId("appointment_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("created_by")->nullable()->constrained("users")->nullOnDelete();

            $table->dateTime("visited_at")->index();
            $table->string("reason")->nullable();

            // Testo clinico cifrato
            $table->text("objective_exam")->nullable();
            $table->text("diagnosis")->nullable();
            $table->text("treatment_performed")->nullable();
            $table->text("recommendations")->nullable();

            $table->date("next_visit_at")->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("clinical_visits");
    }
};
