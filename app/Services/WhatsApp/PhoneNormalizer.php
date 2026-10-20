<?php

namespace App\Services\WhatsApp;

class PhoneNormalizer
{
    /**
     * Normaliza un telefono a formato E.164 (con prefijo +).
     * Devuelve null si no hay digitos suficientes o excede el largo permitido.
     */
    public static function normalize(?string $phone): ?string
    {
        $raw = trim((string) $phone);

        if ($raw === '') {
            return null;
        }

        $international = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $international = true;
        }

        $countryCode = preg_replace('/\D+/', '', (string) config('whatsapp.default_country_code', '51')) ?: '51';

        if (! $international && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! $international && ! str_starts_with($digits, $countryCode)) {
            $digits = $countryCode.$digits;
        }

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return '+'.$digits;
    }

    /**
     * Formato que espera Meta Cloud API: solo digitos con codigo de pais.
     */
    public static function toApiNumber(?string $phone): ?string
    {
        $normalized = self::normalize($phone);

        return $normalized === null ? null : ltrim($normalized, '+');
    }
}
