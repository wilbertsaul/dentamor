<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_phone_number_id',
                'whatsapp_business_account_id',
                'whatsapp_token',
                'whatsapp_api_version',
                'whatsapp_template_name',
                'whatsapp_template_lang',
            ]);

            $table->string('whatsapp_session')->default('default')->after('whatsapp_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('whatsapp_session');

            $table->string('whatsapp_phone_number_id')->nullable()->after('whatsapp_enabled');
            $table->string('whatsapp_business_account_id')->nullable()->after('whatsapp_phone_number_id');
            $table->text('whatsapp_token')->nullable()->after('whatsapp_business_account_id');
            $table->string('whatsapp_api_version', 10)->default('v21.0')->after('whatsapp_token');
            $table->string('whatsapp_template_name')->nullable()->after('whatsapp_api_version');
            $table->string('whatsapp_template_lang', 10)->default('es')->after('whatsapp_template_name');
        });
    }
};
