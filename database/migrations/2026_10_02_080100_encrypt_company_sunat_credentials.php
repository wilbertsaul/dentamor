<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = ['sunat_password', 'certificate_password', 'sunat_password_secondary'];

    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        DB::table('companies')->orderBy('id')->each(function ($row): void {
            $updates = [];

            foreach (self::FIELDS as $field) {
                $value = $row->{$field} ?? null;

                if (! is_string($value) || $value === '') {
                    continue;
                }

                if (self::isEncrypted($value)) {
                    continue;
                }

                $updates[$field] = Crypt::encryptString($value);
            }

            if ($updates !== []) {
                DB::table('companies')->where('id', $row->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        DB::table('companies')->orderBy('id')->each(function ($row): void {
            $updates = [];

            foreach (self::FIELDS as $field) {
                $value = $row->{$field} ?? null;

                if (! is_string($value) || $value === '' || ! self::isEncrypted($value)) {
                    continue;
                }

                $updates[$field] = Crypt::decryptString($value);
            }

            if ($updates !== []) {
                DB::table('companies')->where('id', $row->id)->update($updates);
            }
        });
    }

    private static function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
};