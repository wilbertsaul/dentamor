<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sunat_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 2);
            $table->string('reference', 32);
            $table->date('reference_date');
            $table->date('issue_date');
            $table->string('environment', 12)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('ticket', 50)->nullable();
            $table->string('code', 10)->nullable();
            $table->text('description')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->unsignedInteger('documents_count')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'reference']);
            $table->index(['company_id', 'type', 'reference_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_operations');
    }
};