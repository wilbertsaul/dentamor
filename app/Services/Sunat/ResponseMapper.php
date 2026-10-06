<?php

namespace App\Services\Sunat;

class ResponseMapper
{
    public const ACCEPTED = 'accepted';

    public const ACCEPTED_WITH_OBSERVATIONS = 'accepted_with_observations';

    public const REJECTED = 'rejected';

    public const ERROR = 'error';

    /**
     * Códigos de respuesta del CDR (catálogo 20).
     * 0 aceptada, 1 aceptada con observaciones, 2 rechazada, 98/99 error del sistema.
     * El rango 4000+ corresponde a validaciones que no impiden la validez tributaria.
     */
    public static function status(?string $code): string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return self::ERROR;
        }

        $numeric = (int) $code;

        return match (true) {
            $numeric === 0 => self::ACCEPTED,
            $numeric === 1 => self::ACCEPTED_WITH_OBSERVATIONS,
            $numeric >= 4000 => self::ACCEPTED_WITH_OBSERVATIONS,
            $numeric === 98, $numeric === 99 => self::ERROR,
            default => self::REJECTED,
        };
    }

    public static function isValid(?string $status): bool
    {
        return in_array($status, [self::ACCEPTED, self::ACCEPTED_WITH_OBSERVATIONS], true);
    }

    public static function label(?string $status): string
    {
        return match ($status) {
            self::ACCEPTED => 'Aceptada',
            self::ACCEPTED_WITH_OBSERVATIONS => 'Aceptada con observaciones',
            self::REJECTED => 'Rechazada',
            self::ERROR => 'Error de envío',
            'pending' => 'Pendiente',
            'sent' => 'Enviado a SUNAT',
            'processing' => 'En proceso',
            'draft' => 'Borrador',
            'voided' => 'Anulada',
            default => (string) $status,
        };
    }
}
