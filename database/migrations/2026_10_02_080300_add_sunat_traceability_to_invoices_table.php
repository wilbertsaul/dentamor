<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('sunat_document_type', 2)->nullable()->after('invoice_type');
            $table->foreignId('related_invoice_id')->nullable()->after('client_id')->constrained('invoices')->nullOnDelete();
            $table->foreignId('sunat_operation_id')->nullable()->after('related_invoice_id')->constrained('sunat_operations')->nullOnDelete();
            $table->string('sunat_environment', 12)->nullable()->after('production');
            $table->string('sunat_code', 10)->nullable()->after('sunat_status');
            $table->text('sunat_notes')->nullable()->after('sunat_description');
            $table->string('sunat_ticket', 50)->nullable()->after('sunat_code');
            $table->string('sunat_xml_name', 40)->nullable()->after('xml_path');
            $table->timestamp('sunat_sent_at')->nullable()->after('sunat_ticket');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_invoice_id');
            $table->dropConstrainedForeignId('sunat_operation_id');
            $table->dropColumn([
                'sunat_document_type', 'sunat_environment', 'sunat_code',
                'sunat_notes', 'sunat_ticket', 'sunat_xml_name', 'sunat_sent_at',
            ]);
        });
    }
};