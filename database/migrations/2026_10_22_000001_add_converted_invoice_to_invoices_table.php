<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('converted_invoice_id')
                ->nullable()
                ->after('sunat_operation_id')
                ->constrained('invoices')
                ->nullOnDelete();
        });

        DB::table('invoices')
            ->where('invoice_type', 'R')
            ->where('sunat_status', 'converted')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_invoice_id');
        });
    }
};
