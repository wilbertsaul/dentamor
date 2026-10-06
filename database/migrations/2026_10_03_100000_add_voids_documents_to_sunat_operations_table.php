<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sunat_operations', function (Blueprint $table) {
            $table->boolean('voids_documents')->default(false)->after('documents_count');
        });
    }

    public function down(): void
    {
        Schema::table('sunat_operations', function (Blueprint $table) {
            $table->dropColumn('voids_documents');
        });
    }
};
