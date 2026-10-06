<?php

namespace App\Services\Sunat;

use App\Models\Client;
use Greenter\Model\Client\Client as GreenterClient;
use Greenter\Model\Company\Address;

class ClientBuilder
{
    /**
     * Catálogo 06 - Tipo de documento de identidad del adquirente.
     */
    public const DOC_TYPES = [
        'DNI' => '1',
        'RUC' => '6',
        'CE' => '4',
        'N' => '0',
        'NONE' => '0',
        '1' => '1',
        '4' => '4',
        '6' => '6',
        '0' => '0',
    ];

    public const ANONYMOUS_DOC = '00000000';

    public const ANONYMOUS_NAME = 'CONSUMIDOR FINAL';

    /**
     * A partir de S/ 700 la boleta exige identificar al adquirente (Art. 4 del Reglamento de Comprobantes).
     */
    public const DOCUMENT_REQUIRED_FROM = 700.0;

    public static function build(?Client $client): GreenterClient
    {
        if ($client === null || static::isAnonymous($client)) {
            return static::anonymous();
        }

        $tipoDoc = static::tipoDocFor($client);

        if ($tipoDoc === '0') {
            return static::anonymous(filled($client->name) ? $client->name : null);
        }

        $greenterClient = new GreenterClient;
        $greenterClient->setTipoDoc($tipoDoc)
            ->setNumDoc($client->doc_number)
            ->setRznSocial($client->name ?: static::ANONYMOUS_NAME);

        if (filled($client->address)) {
            $address = new Address;
            $address->setCodigoPais('PE')
                ->setDireccion($client->address);
            $greenterClient->setAddress($address);
        }

        if (filled($client->phone)) {
            $greenterClient->setTelephone($client->phone);
        }

        if (filled($client->email)) {
            $greenterClient->setEmail($client->email);
        }

        return $greenterClient;
    }

    public static function anonymous(?string $name = null): GreenterClient
    {
        $greenterClient = new GreenterClient;
        $greenterClient->setTipoDoc('0')
            ->setNumDoc(static::ANONYMOUS_DOC)
            ->setRznSocial(filled($name) ? $name : static::ANONYMOUS_NAME);

        return $greenterClient;
    }

    public static function isAnonymous(?Client $client): bool
    {
        if ($client === null || blank($client->doc_number)) {
            return true;
        }

        if ($client->doc_number === static::ANONYMOUS_DOC) {
            return true;
        }

        return in_array((string) $client->doc_type, ['N', 'NONE', '0'], true);
    }

    public static function tipoDocFor(?Client $client): string
    {
        if (static::isAnonymous($client)) {
            return '0';
        }

        $tipoDoc = static::DOC_TYPES[$client->doc_type] ?? null;

        if ($tipoDoc !== null && $tipoDoc !== '0') {
            return $tipoDoc;
        }

        return strlen((string) preg_replace('/\D/', '', $client->doc_number)) === 11 ? '6' : '1';
    }

    public static function requiresDocument(float $total): bool
    {
        return $total > static::DOCUMENT_REQUIRED_FROM;
    }
}
