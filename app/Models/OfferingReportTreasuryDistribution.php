<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferingReportTreasuryDistribution extends Model
{
    protected $fillable = [
        'offering_report_id',
        'treasury_id',
        'amount_bs',
        'status',
    ];

    // Relación con el informe
    public function offeringReport()
    {
        return $this->belongsTo(OfferingReport::class, 'offering_report_id');
    }

    // Relación con la tesorería
    public function treasury()
    {
        return $this->belongsTo(Treasury::class, 'treasury_id');
    }

    /**
     * Calcular y guardar las distribuciones financieras para un informe.
     *
     * @param int $offeringReportId ID del informe
     */
    public static function calculateTreasuryDistribution($offeringReportId)
    {
        // Obtener el informe
        $offeringReport = \App\Models\OfferingReport::find($offeringReportId);

        if (!$offeringReport) {
            throw new \Exception("El informe con ID {$offeringReportId} no existe.");
        }

        $grandTotalBs = $offeringReport->grand_total_bs; // Monto total a distribuir

        if ($grandTotalBs <= 0) {
            throw new \Exception("El gran total del informe debe ser mayor que cero.");
        }

        // Obtener las distribuciones para cada categoría de ofrenda en el informe
        $distributions = \App\Models\OfferingTreasuryDistribution::whereIn(
            'offering_id',
            $offeringReport->offeringItems()->pluck('offering_id')
        )->get();

        foreach ($distributions as $distribution) {
            // Calcular el monto distribuido
            $distributedAmount = ($grandTotalBs * $distribution->percentage) / 100;

            // Guardar la distribución específica para este informe
            self::create([
                'offering_report_id' => $offeringReportId,
                'treasury_id' => $distribution->treasury_id,
                'amount_bs' => $distributedAmount,
                'status' => 'pending', // Estado inicial: pendiente
            ]);
        }
    }
}