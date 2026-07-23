<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('clinical_visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('number')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->date('issued_at')->nullable();

            $table->string('client_name');
            $table->string('client_fiscal_code', 16)->nullable();
            $table->string('client_vat', 20)->nullable();
            $table->string('client_address')->nullable();
            $table->string('client_city')->nullable();
            $table->string('client_cap', 10)->nullable();
            $table->string('client_province', 4)->nullable();

            $table->decimal('taxable', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('withholding_amount', 10, 2)->default(0);
            $table->decimal('stamp_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('net_to_pay', 10, 2)->default(0);

            $table->boolean('vat_exempt')->default(true);
            $table->string('vat_nature', 4)->nullable();
            $table->string('regime', 20)->nullable();

            $table->string('payment_method')->nullable();
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['year', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
