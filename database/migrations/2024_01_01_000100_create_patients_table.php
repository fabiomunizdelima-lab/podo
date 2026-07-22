<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('fiscal_code', 16)->nullable()->index();
            $table->date('birth_date')->nullable();
            $table->string('gender', 1)->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp_phone')->nullable();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('province', 4)->nullable();

            $table->text('notes')->nullable();
            // Dati sanitari cifrati a riposo (colonna text: contiene payload cifrato)
            $table->text('clinical_notes')->nullable();

            // Consensi GDPR
            $table->boolean('consent_privacy')->default(false);
            $table->boolean('consent_whatsapp')->default(false);
            $table->boolean('consent_marketing')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
