<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('boleta_serie', 10)->default('B002')->after('sunat_phone');
            $table->string('factura_serie', 10)->default('F002')->after('boleta_serie');
            $table->string('nc_boleta_serie', 10)->default('BC02')->after('factura_serie');
            $table->string('nc_factura_serie', 10)->default('FC02')->after('nc_boleta_serie');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['boleta_serie', 'factura_serie', 'nc_boleta_serie', 'nc_factura_serie']);
        });
    }
};