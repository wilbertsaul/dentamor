<?php

namespace App\Services\Sunat;

use App\Models\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company as Emitter;

class CompanyBuilder
{
    public static function build(Company $company): Emitter
    {
        SeeFactory::assertAddress($company);

        $address = new Address;
        $address->setUbigueo($company->sunat_ubigeo)
            ->setDepartamento($company->sunat_department)
            ->setProvincia($company->sunat_province)
            ->setDistrito($company->sunat_district)
            ->setDireccion($company->address)
            ->setCodLocal($company->establishmentCode())
            ->setCodigoPais('PE');

        if (filled($company->sunat_urbanization)) {
            $address->setUrbanizacion($company->sunat_urbanization);
        }

        $emitter = new Emitter;
        $emitter->setRuc($company->ruc)
            ->setRazonSocial($company->business_name)
            ->setNombreComercial(filled($company->commercial_name)
                ? $company->commercial_name
                : $company->business_name)
            ->setAddress($address);

        if (filled($company->sunat_email)) {
            $emitter->setEmail($company->sunat_email);
        }

        if (filled($company->sunat_phone)) {
            $emitter->setTelephone($company->sunat_phone);
        }

        return $emitter;
    }
}
