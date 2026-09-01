<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Company::create([
            'ruc' => '20000000001',
            'business_name' => 'DEMO DENTAL CLINIC SAC',
            'commercial_name' => 'SISDENT',
            'address' => 'AV. PRUEBA 123, LIMA',
            'sunat_username' => 'MODDATOS',
            'sunat_password' => 'moddatos',
            'certificate_path' => 'sunat/certificates/test_cert.pem',
            'certificate_password' => null,
            'production' => false,
        ]);

        \App\Models\Client::create([
            'doc_type' => 'DNI',
            'doc_number' => '12345678',
            'name' => 'JUAN PEREZ GARCIA',
            'email' => 'juan@email.com',
            'phone' => '999888777',
            'address' => 'JR. PRUEBA 456',
        ]);

        \App\Models\Client::create([
            'doc_type' => 'DNI',
            'doc_number' => '87654321',
            'name' => 'MARIA LOPEZ HUAMAN',
            'email' => 'maria@email.com',
            'phone' => '999777666',
            'address' => 'AV. PRINCIPAL 789',
        ]);

        \App\Models\Client::create([
            'doc_type' => 'RUC',
            'doc_number' => '20123456789',
            'name' => 'EMPRESA DE SEGUROS DENTAL SA',
            'email' => 'seguros@empresa.com',
            'phone' => '5115551234',
            'address' => 'AV. EMPRESARIAL 1000',
        ]);

        $services = [
            ['code' => 'CON001', 'name' => 'CONSULTA GENERAL', 'description' => 'Consulta odontológica general', 'price' => 50.00],
            ['code' => 'LIM002', 'name' => 'LIMPIEZA DENTAL', 'description' => 'Profilaxis dental completa', 'price' => 80.00],
            ['code' => 'EMP003', 'name' => 'EMPASTE RESINA', 'description' => 'Empaste con resina compuesta', 'price' => 120.00],
            ['code' => 'EXT004', 'name' => 'EXTRACCIÓN SIMPLE', 'description' => 'Extracción dental simple', 'price' => 100.00],
            ['code' => 'END005', 'name' => 'ENDODONCIA', 'description' => 'Tratamiento de conducto', 'price' => 250.00],
            ['code' => 'COR006', 'name' => 'CORONA METAL PORCELANA', 'description' => 'Corona dental metal-porcelana', 'price' => 500.00],
            ['code' => 'BLA007', 'name' => 'BLANQUEAMIENTO', 'description' => 'Blanqueamiento dental', 'price' => 350.00],
            ['code' => 'ORT008', 'name' => 'ORTODONCIA MENSUAL', 'description' => 'Cuota mensual de ortodoncia', 'price' => 200.00],
        ];

        foreach ($services as $svc) {
            \App\Models\Service::create($svc);
        }

        \App\Models\User::create([
            'name' => 'Admin SISDENT',
            'email' => 'admin@sisdent.com',
            'password' => bcrypt('password'),
        ]);
    }
}
