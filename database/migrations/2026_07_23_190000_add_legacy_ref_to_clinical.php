<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riferimento al record storico SmartPodos, per import idempotente
 * delle cartelle cliniche e delle ortesi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_visits', function (Blueprint $table) {
            $table->string('legacy_ref')->nullable()->unique();
        });
        Schema::table('orthoses', function (Blueprint $table) {
            $table->string('legacy_ref')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('clinical_visits', fn (Blueprint $t) => $t->dropColumn('legacy_ref'));
        Schema::table('orthoses', fn (Blueprint $t) => $t->dropColumn('legacy_ref'));
    }
};
