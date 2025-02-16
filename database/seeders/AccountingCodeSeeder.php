<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountingCode; // Asegúrate de que el modelo esté correctamente importado

class AccountingCodeSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['accounting_id' => 7,
            'role_id' => 1,
            'movement_id' => 1,
            'code' => 'I-100',
            'description' => 'SALDO ANTERIOR',
            'active' => true,
            'created_at' => '2021-08-01 18:42:36',
            'updated_at' => null],
            
            ['accounting_id' => 7, 'role_id' => 1, 'movement_id' => 1, 'code' => 'I-101', 'description' => 'DIEZMOS', 'active' => true, 'created_at' => '2021-08-01 18:42:36', 'updated_at' => null],
            ['accounting_id' => 7, 'role_id' => 1, 'movement_id' => 1, 'code' => 'I-102', 'description' => 'EL PODER DEL UNO', 'active' => true, 'created_at' => '2021-08-01 18:42:36', 'updated_at' => null],
            ['accounting_id' => 7, 'role_id' => 1, 'movement_id' => 1, 'code' => 'I-103', 'description' => 'SEDE NACIONAL', 'active' => true, 'created_at' => '2021-08-01 18:42:36', 'updated_at' => '2024-09-29 20:05:24'],
            // Agrega más filas aquí según tu necesidad...
        ];

        foreach ($data as $item) {
            AccountingCode::updateOrCreate(
                ['code' => $item['code']], // Actualiza si ya existe el código
                $item
            );
        }
    }
}