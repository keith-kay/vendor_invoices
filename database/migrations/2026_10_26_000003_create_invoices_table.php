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
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->string('currency', 3)->default('KES');
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('receipt_channel')->nullable();
            $table->string('category')->nullable();
            $table->string('status')->default('registered')->index();
            $table->boolean('claim_required')->default(false);
            $table->json('attachments')->nullable();
            $table->string('posting_reference')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['vendor_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
