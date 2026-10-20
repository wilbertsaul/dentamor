<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('customer_phone', 20)->nullable()->after('pdf_path');
            $table->string('customer_email')->nullable()->after('customer_phone');
            $table->string('whatsapp_status', 20)->nullable()->after('customer_email');
            $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_status');
            $table->string('whatsapp_message_id')->nullable()->after('whatsapp_sent_at');
            $table->text('whatsapp_error')->nullable()->after('whatsapp_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'customer_phone',
                'customer_email',
                'whatsapp_status',
                'whatsapp_sent_at',
                'whatsapp_message_id',
                'whatsapp_error',
            ]);
        });
    }
};
