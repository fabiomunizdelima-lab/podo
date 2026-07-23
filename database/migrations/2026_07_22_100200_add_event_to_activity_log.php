<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spatie/laravel-activitylog v4 registra il tipo di evento (created/updated/deleted)
 * nella colonna "event". La migration originale di activity_log la ometteva,
 * facendo fallire ogni scrittura di audit log (pazienti, appuntamenti, listino).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table("activity_log", function (Blueprint $table) {
            if (! Schema::hasColumn("activity_log", "event")) {
                $table->string("event")->nullable()->after("subject_id");
            }
        });
    }

    public function down(): void
    {
        Schema::table("activity_log", function (Blueprint $table) {
            if (Schema::hasColumn("activity_log", "event")) {
                $table->dropColumn("event");
            }
        });
    }
};
