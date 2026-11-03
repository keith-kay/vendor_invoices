<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cargowise_job_ref')->index();
            $table->string('cost_type')->nullable();
            $table->decimal('allocated_amount', 15, 2);
            $table->boolean('accrual_confirmed')->default(false);
            $table->foreignId('accrual_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accrual_confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_allocations');
    }
};
