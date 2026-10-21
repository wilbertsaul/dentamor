<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('boleta_serie_beta', 10)->default('PR01')->after('boleta_serie');
            $table->string('factura_serie_beta', 10)->default('PR02')->after('factura_serie');
            $table->string('nc_boleta_serie_beta', 10)->default('PR03')->after('nc_boleta_serie');
            $table->string('nc_factura_serie_beta', 10)->default('PR04')->after('nc_factura_serie');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'boleta_serie_beta',
                'factura_serie_beta',
                'nc_boleta_serie_beta',
                'nc_factura_serie_beta',
            ]);
        });
    }
};
