<?php

namespace Database\Seeders;

use App\Models\Accounting;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        // 🔁 Tesorero Nacional
        if ($user = User::role('Tesorero Nacional')->first()) {
            Accounting::create([
                'name' => 'Contabilidad Nacional',
                'description' => 'Contabilidad Nacional',
                'treasury_id' => Treasury::where('level', 'Nacional')->first()?->id,
                'region_id' => null,
                'district_id' => null,
                'sector_id' => null,
            ]);
        }

        // 🔁 Tesoreros Regionales
        User::role('Tesorero Regional')->get()->each(function ($user) {
            $treasury = Treasury::where('level', 'Regional')->first();
            Accounting::create([
                'name' => 'Contabilidad Regional',
                'description' => 'Contabilidad Regional',
                'treasury_id' => $treasury?->id,
                'region_id' => $user->region_id,
                'district_id' => null,
                'sector_id' => null,
            ]);
        });

        // 🔁 Supervisores Distritales
        User::role('Supervisor Distrital')->get()->each(function ($user) {
            $treasury = Treasury::where('level', 'Distrital')->first();
            Accounting::create([
                'name' => 'Contabilidad Distrital',
                'description' => 'Contabilidad Distrital',
                'treasury_id' => $treasury?->id,
                'region_id' => $user->district?->region_id,
                'district_id' => $user->district_id,
                'sector_id' => null,
            ]);
        });

        // 🔁 Tesoreros Sectoriales
        User::role('Tesorero Sectorial')->get()->each(function ($user) {
            $treasury = Treasury::where('level', 'Sectorial')->first();
            Accounting::create([
                'name' => 'Contabilidad Sectorial',
                'description' => 'Contabilidad Sectorial',
                'treasury_id' => $treasury?->id,
                'region_id' => $user->sector?->district?->region_id,
                'district_id' => $user->sector?->district_id,
                'sector_id' => $user->sector_id,
            ]);
        });
    }
}