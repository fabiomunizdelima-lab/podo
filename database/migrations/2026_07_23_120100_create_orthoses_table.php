<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ortesi / plantari su misura (modulo "Ortesi su misura" di SmartPodos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orthoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type');                          // Plantare, Ortesi digitale, ...
            $table->string('foot', 10)->nullable();          // L / R / both
            $table->string('material')->nullable();
            $table->text('specifications')->nullable();      // misure / note tecniche
            $table->string('status', 20)->default('prescribed')->index();
            $table->decimal('price', 10, 2)->default(0);

            $table->date('prescribed_at')->nullable();
            $table->date('delivered_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orthoses');
    }
};
