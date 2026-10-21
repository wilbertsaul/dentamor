<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las series beta empezaron como PR01/PR02/PR03/PR04, pero SUNAT exige
     * series que inicien con F o B (Invoice::isElectronicSerie). Se renombran
     * a BP01/FP01/BC01/FC01 conservando cualquier valor personalizado distinto
     * de los antiguos defaults.
     */
    private const MAP = [
        'boleta_serie_beta' => ['PR01', 'BP01'],
        'factura_serie_beta' => ['PR02', 'FP01'],
        'nc_boleta_serie_beta' => ['PR03', 'BC01'],
        'nc_factura_serie_beta' => ['PR04', 'FC01'],
    ];

    public function up(): void
    {
        foreach (self::MAP as $column => [$old, $new]) {
            DB::table('companies')->where($column, $old)->update([$column => $new]);

            Schema::table('companies', function (Blueprint $table) use ($column, $new) {
                $table->string($column, 10)->default($new)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $column => [$old, $new]) {
            DB::table('companies')->where($column, $new)->update([$column => $old]);

            Schema::table('companies', function (Blueprint $table) use ($column, $old) {
                $table->string($column, 10)->default($old)->change();
            });
        }
    }
};
