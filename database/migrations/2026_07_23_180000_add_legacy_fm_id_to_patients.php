<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ID paziente ereditato da SmartPodos (FileMaker, campo "N PAZIENTE").
 * Serve ad agganciare le cartelle cliniche importate dal gestionale storico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('legacy_fm_id')->nullable()->index()->after('fiscal_code');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('legacy_fm_id');
        });
    }
};
