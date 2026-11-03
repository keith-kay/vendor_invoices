<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Used in the KRA eTIMS phase; created now so the model is ready.
    public function up(): void
    {
        Schema::create('etims_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('response_code')->nullable();
            $table->string('kra_reference')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etims_validations');
    }
};
