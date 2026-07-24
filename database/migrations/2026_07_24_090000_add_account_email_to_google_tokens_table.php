<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indirizzo dell'account Google collegato: serve solo a mostrarlo
 * nella pagina Impostazioni -> Integrazioni. Migrazione additiva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_tokens', function (Blueprint $table) {
            $table->string('account_email')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('google_tokens', function (Blueprint $table) {
            $table->dropColumn('account_email');
        });
    }
};
