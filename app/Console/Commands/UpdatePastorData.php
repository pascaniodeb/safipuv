<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PastorMinistry;
use Illuminate\Support\Carbon;

class UpdatePastorData extends Command
{
    protected $signature = 'pastors:update-data';
    protected $description = 'Actualizar licencias y niveles pastorales según el tiempo y posiciones actuales';

    public function handle()
    {
        // Obtener todos los registros de PastorMinistry
        $pastorMinistries = PastorMinistry::all();

        foreach ($pastorMinistries as $ministry) {
            $startDate = $ministry->start_date_ministry;
            $currentPosition = $ministry->currentPosition;

            // Validar que la fecha de inicio del ministerio exista
            if ($startDate) {
                $startDate = Carbon::parse($startDate)->startOfDay();
                $today = now()->startOfDay();
                $yearsInMinistry = $startDate->diffInYears($today);

                // Actualizar la licencia pastoral
                if ($yearsInMinistry <= 3) {
                    $ministry->pastor_licence_id = 1; // LOCAL
                } elseif ($yearsInMinistry > 3 && $yearsInMinistry <= 6) {
                    $ministry->pastor_licence_id = 2; // NACIONAL
                } elseif ($yearsInMinistry > 6) {
                    $ministry->pastor_licence_id = 3; // ORDENACIÓN
                }

                // Actualizar el nivel pastoral basado en años
                if ($yearsInMinistry <= 6) {
                    $ministry->pastor_level_id = 1; // BRONCE
                } elseif ($yearsInMinistry >= 7 && $yearsInMinistry <= 12) {
                    $ministry->pastor_level_id = 2; // PLATA
                } elseif ($yearsInMinistry >= 13 && $yearsInMinistry <= 20) {
                    $ministry->pastor_level_id = 3; // TITANIO
                } elseif ($yearsInMinistry >= 21 && $yearsInMinistry <= 35) {
                    $ministry->pastor_level_id = 4; // ORO
                } elseif ($yearsInMinistry >= 36) {
                    $ministry->pastor_level_id = 5; // PLATINO
                }

                // Actualizar el nivel pastoral basado en la posición actual
                if ($currentPosition) {
                    if ($currentPosition->name === 'Asesor Ejecutivo') {
                        $ministry->pastor_level_id = 6; // PLATINO PLUS
                    } elseif (in_array($currentPosition->name, ['Obispo', 'Obispo Vicepresidente'])) {
                        $ministry->pastor_level_id = 7; // DIAMANTE
                    } elseif ($currentPosition->name === 'Obispo Presidente') {
                        $ministry->pastor_level_id = 8; // ZAFIRO
                    }
                }

                // Guardar los cambios
                $ministry->save();
            }
        }

        $this->info('Licencias y niveles pastorales actualizados correctamente.');
    }
}