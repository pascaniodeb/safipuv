<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            ['location_id' => 1, 'bank_code' => '0102', 'name' => 'BANCO DE VENEZUELA', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0104', 'name' => 'VENEZOLANO DE CRÉDITO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0105', 'name' => 'BANCO MERCANTIL', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0108', 'name' => 'BBVA BANCO PROVINCIAL', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0114', 'name' => 'BANCARIBE', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0115', 'name' => 'BANCO EXTERIOR', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0116', 'name' => 'BANCO OCCIDENTAL DE DESCUENTO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0128', 'name' => 'BANCO CARONÍ', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0134', 'name' => 'BANESCO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0137', 'name' => 'BANCO SOFITASA', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0138', 'name' => 'BANCO PLAZA', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0146', 'name' => 'BANCO DE LA GENTE EMPRENDEDORA', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0149', 'name' => 'BANCO DEL PUEBLO SOBERANO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0151', 'name' => 'BFC BANCO FONDO COMÚN', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0156', 'name' => '100%BANCO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0157', 'name' => 'DEL SUR', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0163', 'name' => 'BANCO DEL TESORO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0166', 'name' => 'BANCO AGRÍCOLA DE VENEZUELA', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0168', 'name' => 'BANCRECER', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0169', 'name' => 'MI BANCO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0171', 'name' => 'BANCO ACTIVO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0172', 'name' => 'BANCAMIGA', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0173', 'name' => 'BANCO INTERNACIONAL DE DESARROLLO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0174', 'name' => 'BANPLUS', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0175', 'name' => 'BANCO BICENTENARIO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0176', 'name' => 'BANCO ESPIRITO SANTO', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0177', 'name' => 'BANFANB', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0190', 'name' => 'CITIBANK', 'active' => true],
            ['location_id' => 1, 'bank_code' => '0191', 'name' => 'BNC', 'active' => true],
        ];

        // Ordenar los bancos por código de banco de menor a mayor
        usort($banks, function ($a, $b) {
            return $a['bank_code'] <=> $b['bank_code'];
        });

        DB::table('banks')->insert($banks);
    }
}