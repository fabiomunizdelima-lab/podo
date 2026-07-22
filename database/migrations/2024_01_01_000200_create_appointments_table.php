<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('status')->default('scheduled')->index();
            $table->string('treatment')->nullable();
            $table->text('notes')->nullable();

            // Sincronizzazione Google Calendar
            $table->string('google_event_id')->nullable()->index();

            // Promemoria (whatsapp/email/none)
            $table->string('reminder_channel')->default('whatsapp');
            $table->timestamp('reminder_sent_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
