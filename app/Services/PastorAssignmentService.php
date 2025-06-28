<?php

namespace App\Services;

use App\Models\Pastor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class PastorAssignmentService
{
    /**
     * Asigna licencia y nivel a un pastor según su información.
     */
    public function assignLicenceAndLevel(Pastor $pastor)
    {
        // Calculamos días en el ministerio
        $daysInMinistry = 0;
        if ($pastor->start_date_ministry) {
            $daysInMinistry = Carbon::parse($pastor->start_date_ministry)->diffInDays(now());
        }

        // ✅ En lugar de asignar en $pastor,
        // asigna en $pastor->pastorMinistry
        $ministry = $pastor->pastorMinistry; // O ->pastorMinistries()->first() si es 1:N

        // Si no existe, puedes crear uno o retornar
        if (! $ministry) {
            return; // o crear un record en pastor_ministries si tu lógica lo requiere
        }

        // Asignar la licencia según la lógica
        $ministry->pastor_licence_id = $this->getLicence(
            $pastor->pastor_income_id,
            $pastor->pastor_type_id,
            $daysInMinistry,
            $ministry->pastor_licence_id
        );

        // Asignar el nivel
        $ministry->pastor_level_id = $this->getLevel(
            $daysInMinistry,
            $ministry->current_position_id,
            $ministry->pastor_level_id
        );

        // Guardar en la tabla pastor_ministries
        $ministry->save();
    }


    /**
     * Lógica final para asignar la licencia (para uso interno en assignLicenceAndLevel).
     *
     * @param  int|null $incomeId
     * @param  int|null $typeId
     * @param  int      $daysInMinistry
     * @param  int|null $currentLicenceId
     * @return int
     */
    private function getLicence(?int $incomeId, ?int $typeId, int $daysInMinistry, ?int $currentLicenceId): int
    {
        try {
            if (is_null($incomeId) || is_null($typeId)) {
                return $currentLicenceId ?? 1;
            }

            if ($incomeId === 1 && $typeId === 1) {
                return $daysInMinistry <= 1000 ? 1 : ($daysInMinistry <= 2100 ? 2 : 3);
            }

            if ($incomeId === 2 && in_array($typeId, [1, 2])) {
                return $daysInMinistry <= 1000 ? 2 : 3;
            }

            if ($incomeId === 1 && $typeId === 3) {
                return $daysInMinistry <= 1000 ? 1 : 2;
            }

            if ($incomeId === 3 && $typeId === 4) {
                return 2;
            }

            return $currentLicenceId ?? 1;
        } catch (\Throwable $e) {
            \Log::error('Error en getLicence: '.$e->getMessage());
            return $currentLicenceId ?? 1;
        }
    }


    /**
     * Lógica final para asignar el nivel (para uso interno en assignLicenceAndLevel).
     *
     * @param  int      $daysInMinistry
     * @param  int|null $currentPositionId
     * @param  int|null $currentLevelId
     * @return int
     */
    private function getLevel(int $daysInMinistry, ?int $currentPositionId, ?int $currentLevelId): int
    {
        // Primero, asignamos según los días.
        // 0..2100 => 1 (BRONCE)
        // 2101..4300 => 2 (PLATA)
        // 4301..7200 => 3 (TITANIO)
        // 7201..12700 => 4 (ORO)
        // >=12701 => 5 (PLATINO)

        $levelByDays = 1; // default
        if ($daysInMinistry <= 2100) {
            $levelByDays = 1; // BRONCE
        } elseif ($daysInMinistry <= 4300) {
            $levelByDays = 2; // PLATA
        } elseif ($daysInMinistry <= 7200) {
            $levelByDays = 3; // TITANIO
        } elseif ($daysInMinistry <= 12700) {
            $levelByDays = 4; // ORO
        } else {
            $levelByDays = 5; // PLATINO
        }

        // Segundo, si la posición actual es una de las especiales, override.
        // id=1 => 8 (ZAFIRO)
        // id=2 => 7 (DIAMANTE)
        // id=22 => 6 (PLATINO PLUS)
        if ($currentPositionId === 1) {
            return 8; // ZAFIRO
        } elseif ($currentPositionId === 2) {
            return 7; // DIAMANTE
        } elseif ($currentPositionId === 22) {
            return 6; // PLATINO PLUS
        }

        // Si no coincide con las posiciones especiales, devolvemos el nivel calculado por días.
        return $levelByDays;
    }

    /**
     * Método estático para uso rápido en Relation Managers.
     * Permite determinar la licencia dado el incomeId, typeId, y fecha de inicio.
     */
    public static function determineLicence(?int $incomeId, ?int $typeId, ?string $startDate): int
    {
        // Si no hay fecha o valores, devolvemos por defecto.
        if (is_null($startDate) || is_null($incomeId) || is_null($typeId)) {
            return 1; // LOCAL por defecto
        }

        $daysInMinistry = Carbon::parse($startDate)->diffInDays(now());

        // Replicamos la misma lógica de getLicence, sin el $currentLicenceId.
        if ($incomeId === 1 && $typeId === 1) {
            if ($daysInMinistry <= 1000) {
                return 1; // LOCAL
            } elseif ($daysInMinistry <= 2100) {
                return 2; // NACIONAL
            } else {
                return 3; // ORDENACIÓN
            }
        }

        if ($incomeId === 2 && in_array($typeId, [1, 2])) {
            if ($daysInMinistry <= 1000) {
                return 2; // NACIONAL
            } else {
                return 3; // ORDENACIÓN
            }
        }

        if ($incomeId === 1 && $typeId === 3) {
            if ($daysInMinistry <= 1000) {
                return 1; // LOCAL
            } else {
                return 2; // NACIONAL
            }
        }

        if ($incomeId === 3 && $typeId === 4) {
            return 2; // NACIONAL
        }

        // Si nada aplica, retornamos 1 por defecto.
        return 1;
    }

    /**
     * Método estático para uso rápido en Relation Managers.
     * Determina el nivel según fecha de inicio y posición actual.
     */
    public static function determineLevel(?string $startDate, ?int $currentPositionId): int
    {
        if (!$startDate) {
            return 1; // BRONCE por defecto
        }

        $daysInMinistry = Carbon::parse($startDate)->diffInDays(now());

        // Calculamos el nivel por días
        $levelByDays = 1;
        if ($daysInMinistry <= 2100) {
            $levelByDays = 1; // BRONCE
        } elseif ($daysInMinistry <= 4300) {
            $levelByDays = 2; // PLATA
        } elseif ($daysInMinistry <= 7200) {
            $levelByDays = 3; // TITANIO
        } elseif ($daysInMinistry <= 12700) {
            $levelByDays = 4; // ORO
        } else {
            $levelByDays = 5; // PLATINO
        }

        // Override por posición especial
        if ($currentPositionId === 1) {
            return 8; // ZAFIRO
        } elseif ($currentPositionId === 2) {
            return 7; // DIAMANTE
        } elseif ($currentPositionId === 22) {
            return 6; // PLATINO PLUS
        }

        return $levelByDays;
    }

    /**
     * Restringir edición manual a roles permitidos.
     */
    public function canManuallyEdit(): bool
    {
        return Gate::any(['admin', 'secretary_national']);
    }
}