<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentazione fotografica clinica (piede/lesioni).
 * I file sono salvati cifrati su disco privato locale; il DB tiene solo i metadati.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create("clinical_photos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("patient_id")->constrained()->cascadeOnDelete();
            $table->foreignId("clinical_visit_id")->nullable()->constrained()->nullOnDelete();

            $table->string("disk", 30)->default("local");
            $table->string("path");
            $table->string("original_name")->nullable();
            $table->string("mime", 100)->nullable();
            $table->unsignedInteger("size")->nullable();
            $table->string("foot", 10)->nullable();   // L / R / both
            $table->string("caption")->nullable();
            $table->dateTime("taken_at")->nullable();

            $table->foreignId("uploaded_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("clinical_photos");
    }
};
