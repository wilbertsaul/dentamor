<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('sunat_ubigeo', 6)->nullable()->after('address');
            $table->string('sunat_department', 100)->nullable()->after('sunat_ubigeo');
            $table->string('sunat_province', 100)->nullable()->after('sunat_department');
            $table->string('sunat_district', 100)->nullable()->after('sunat_province');
            $table->string('sunat_urbanization')->nullable()->after('sunat_district');
            $table->string('sunat_establishment_code', 4)->default('0000')->after('sunat_urbanization');
            $table->string('sunat_email')->nullable()->after('sunat_establishment_code');
            $table->string('sunat_phone', 30)->nullable()->after('sunat_email');
            $table->string('sunat_username_secondary')->nullable()->after('sunat_password');
            $table->text('sunat_password_secondary')->nullable()->after('sunat_username_secondary');

            $table->text('sunat_password')->nullable()->change();
            $table->text('certificate_password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'sunat_ubigeo', 'sunat_department', 'sunat_province', 'sunat_district',
                'sunat_urbanization', 'sunat_establishment_code', 'sunat_email', 'sunat_phone',
                'sunat_username_secondary', 'sunat_password_secondary',
            ]);
        });
    }
};