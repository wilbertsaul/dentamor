<?php

namespace App\Services\Sunat;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class SunatLogger
{
    private static ?LoggerInterface $logger = null;

    public static function info(string $message, array $context = []): void
    {
        static::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        static::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        static::log('error', $message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        try {
            static::logger()->log($level, $message, static::scalars($context));
        } catch (\Throwable) {
            // El log nunca debe romper la emisión de un comprobante.
        }
    }

    private static function logger(): LoggerInterface
    {
        return static::$logger ??= Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/sunat.log'),
            'level' => 'debug',
            'days' => 90,
        ]);
    }

    private static function scalars(array $context): array
    {
        $out = [];

        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $out[$key] = $value;

                continue;
            }

            if (is_array($value)) {
                $out[$key] = static::scalars($value);

                continue;
            }

            $out[$key] = is_object($value) && method_exists($value, 'toArray')
                ? static::scalars($value->toArray())
                : get_debug_type($value);
        }

        return $out;
    }
}
