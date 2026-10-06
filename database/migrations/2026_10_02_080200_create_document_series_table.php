<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('serie', 4);
            $table->string('document_type', 2)->nullable();
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['company_id', 'serie']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_series');
    }
};