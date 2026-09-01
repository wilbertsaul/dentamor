<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 11)->unique();
            $table->string('business_name');
            $table->string('commercial_name')->nullable();
            $table->string('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('sunat_username');
            $table->string('sunat_password');
            $table->string('certificate_path')->nullable();
            $table->string('certificate_password')->nullable();
            $table->boolean('production')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
