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
        Schema::create('payment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->string('client_doc', 15)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 20);
            $table->string('reference')->nullable();
            $table->dateTime('payment_date');
            $table->text('notes')->nullable();
            $table->enum('invoice_type', ['F', 'B'])->default('B');
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'invoiced', 'skipped'])->default('pending');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
