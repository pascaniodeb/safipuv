<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run()
    {
        ExchangeRate::create(['currency' => 'VES', 'rate_to_bs' => 1, 'operation' => '=']);
        ExchangeRate::create(['currency' => 'USD', 'rate_to_bs' => 53.00, 'operation' => '*']);
        ExchangeRate::create(['currency' => 'COP', 'rate_to_bs' => 67.00, 'operation' => '/']);
    }
}