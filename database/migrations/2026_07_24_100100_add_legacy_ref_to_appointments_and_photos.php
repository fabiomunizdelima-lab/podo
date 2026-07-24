<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riferimento al record originale di SmartPodos per l'import idempotente
 * dell'agenda storica e delle foto (stessa convenzione di clinical_visits).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('legacy_ref', 40)->nullable()->unique()->after('reminder_sent_at');
        });
        Schema::table('clinical_photos', function (Blueprint $table) {
            $table->string('legacy_ref', 40)->nullable()->unique()->after('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('legacy_ref');
        });
        Schema::table('clinical_photos', function (Blueprint $table) {
            $table->dropColumn('legacy_ref');
        });
    }
};
