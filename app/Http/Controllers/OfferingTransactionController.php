<?php

namespace App\Http\Controllers;

use App\Models\OfferingTransaction;
use App\Models\TreasuryTransaction;
use App\Models\ExchangeRate;
use App\Models\OfferingTreasuryDistribution;
use Illuminate\Http\Request;

class OfferingTransactionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'offerings' => 'required|array',
            'offerings.*.offering_id' => 'required|exists:offerings,id',
            'offerings.*.amount_bs' => 'required|numeric|min:0',
            'offerings.*.amount_usd' => 'nullable|numeric|min:0',
            'offerings.*.amount_cop' => 'nullable|numeric|min:0',
        ]);

        foreach ($validated['offerings'] as $offering) {
            $exchangeRateUSD = ExchangeRate::where('currency', 'USD')->latest()->value('rate');
            $exchangeRateCOP = ExchangeRate::where('currency', 'COP')->latest()->value('rate');

            $totalBs = $offering['amount_bs'];
            $totalBs += $offering['amount_usd'] * $exchangeRateUSD;
            $totalBs += $offering['amount_cop'] * $exchangeRateCOP;

            $offeringTransaction = OfferingTransaction::create([
                'offering_id' => $offering['offering_id'],
                'amount_bs' => $totalBs,
                'amount_usd' => $offering['amount_usd'] ?? 0,
                'amount_cop' => $offering['amount_cop'] ?? 0,
                'pastor_id' => $request->input('pastor_id'),
            ]);

            $distributions = OfferingTreasuryDistribution::where('offering_id', $offering['offering_id'])->get();

            foreach ($distributions as $distribution) {
                TreasuryTransaction::create([
                    'treasury_id' => $distribution->treasury_id,
                    'amount' => $totalBs * ($distribution->percentage / 100),
                    'offering_transaction_id' => $offeringTransaction->id,
                ]);
            }
        }

        return redirect()->route('pastors.index')->with('success', 'Ofrendas registradas y distribuidas correctamente.');
    }
}