<?php

namespace App\Traits;

use App\Models\Accounting;
use Illuminate\Database\Eloquent\Builder;

trait HasAccountingAccess
{
    protected ?Accounting $cachedAccounting = null;

    public function getUserAccounting(): ?Accounting
    {
        if ($this->cachedAccounting) {
            return $this->cachedAccounting;
        }

        $u = auth()->user();
        if (! $u) return null;

        /* ───── roles por nivel ───── */
        $groups = [
            'sectorial' => ['Presbítero Sectorial', 'Tesorero Sectorial', 'Contralor Sectorial'],
            'distrital' => ['Supervisor Distrital'],
            'regional'  => ['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional'],
            'nacional'  => ['Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional'],
        ];

        foreach ($groups as $level => $roles) {
            if (! $u->hasAnyRole($roles)) continue;

            /* CORRECCIÓN: Buscar contabilidad del nivel Y que tenga transacciones 
               específicamente para los IDs geográficos de este usuario */
            $acc = Accounting::whereHas('treasury', fn ($q) =>
                        $q->whereRaw('LOWER(TRIM(level)) = ?', [strtolower($level)]))

                    ->when($level !== 'nacional', function ($query) use ($level, $u) {
                        // Para niveles no nacionales, verificar que existan transacciones 
                        // con los mismos IDs geográficos del usuario
                        return $query->whereHas('transactions', function ($t) use ($level, $u) {
                            switch ($level) {
                                case 'sectorial':
                                    if ($u->sector_id) {
                                        $t->where('sector_id', $u->sector_id);
                                    } else {
                                        $t->whereRaw('1 = 0'); // Sin sector_id, sin acceso
                                    }
                                    break;
                                    
                                case 'distrital':
                                    if ($u->district_id) {
                                        $t->where('district_id', $u->district_id);
                                    } else {
                                        $t->whereRaw('1 = 0'); // Sin district_id, sin acceso
                                    }
                                    break;
                                    
                                case 'regional':
                                    if ($u->region_id) {
                                        $t->where('region_id', $u->region_id);
                                    } else {
                                        $t->whereRaw('1 = 0'); // Sin region_id, sin acceso
                                    }
                                    break;
                            }
                        });
                    })
                    ->latest()
                    ->first();

            if ($acc) {
                \Log::info("HasAccountingAccess - Usuario {$u->name} (ID: {$u->id}) accede a accounting_id: {$acc->id} (nivel: {$level})");
                return $this->cachedAccounting = $acc;
            }
        }

        \Log::warning("HasAccountingAccess - Usuario {$u->name} (ID: {$u->id}) SIN acceso a ninguna contabilidad");
        return null;   // usuario sin rol contable o sin contabilidad apropiada
    }

    /* ───── scope global para cualquier modelo con accounting_id ───── */
    public function scopeAccessibleRecords(Builder $q): Builder
    {
        $acc = $this->getUserAccounting();

        if (!$acc) {
            \Log::warning("scopeAccessibleRecords - Sin contabilidad, bloqueando acceso");
            return $q->whereRaw('1 = 0');   // sin acceso
        }

        \Log::info("scopeAccessibleRecords - Aplicando filtros para accounting_id: {$acc->id}");

        // Aplicar filtro base por accounting_id
        $q->where('accounting_id', $acc->id);

        // ADEMÁS, aplicar filtros geográficos adicionales según el rol del usuario
        $u = auth()->user();
        if (!$u) {
            \Log::warning("scopeAccessibleRecords - Sin usuario autenticado");
            return $q->whereRaw('1 = 0');
        }

        \Log::info("scopeAccessibleRecords - Usuario: {$u->name} (ID: {$u->id})");
        \Log::info("scopeAccessibleRecords - Tabla del modelo: " . $this->getTable());

        // Verificar si la tabla tiene las columnas geográficas antes de aplicar filtros
        $tableColumns = \Schema::getColumnListing($this->getTable());
        
        // Aplicar filtros geográficos específicos solo si las columnas existen
        if ($u->hasAnyRole(['Presbítero Sectorial', 'Tesorero Sectorial', 'Contralor Sectorial'])) {
            if ($u->sector_id && in_array('sector_id', $tableColumns)) {
                $q->where('sector_id', $u->sector_id);
                \Log::info("scopeAccessibleRecords - Aplicando filtro sectorial: {$u->sector_id}");
            } elseif (!$u->sector_id) {
                \Log::warning("scopeAccessibleRecords - Usuario sectorial sin sector_id");
                $q->whereRaw('1 = 0'); // Sin sector_id, sin acceso
            }
        } elseif ($u->hasRole('Supervisor Distrital')) {
            if ($u->district_id && in_array('district_id', $tableColumns)) {
                $q->where('district_id', $u->district_id);
                \Log::info("scopeAccessibleRecords - Aplicando filtro distrital: {$u->district_id}");
            } elseif (!$u->district_id) {
                \Log::warning("scopeAccessibleRecords - Usuario distrital sin district_id");
                $q->whereRaw('1 = 0'); // Sin district_id, sin acceso
            }
        } elseif ($u->hasAnyRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional'])) {
            if ($u->region_id && in_array('region_id', $tableColumns)) {
                $q->where('region_id', $u->region_id);
                \Log::info("scopeAccessibleRecords - Aplicando filtro regional: {$u->region_id}");
            } elseif (!$u->region_id) {
                \Log::warning("scopeAccessibleRecords - Usuario regional sin region_id");
                $q->whereRaw('1 = 0'); // Sin region_id, sin acceso
            }
        } else {
            \Log::info("scopeAccessibleRecords - Usuario nacional, sin filtros geográficos adicionales");
        }

        \Log::info("scopeAccessibleRecords - Query final: " . $q->toSql());
        \Log::info("scopeAccessibleRecords - Bindings: " . json_encode($q->getBindings()));

        return $q;
    }

    public function canAccessRecord($record): bool
    {
        $acc = $this->getUserAccounting();
        if (!$acc || $acc->id !== $record->accounting_id) {
            return false;
        }

        // Verificación adicional de IDs geográficos
        $u = auth()->user();
        if (!$u) return false;

        if ($u->hasAnyRole(['Presbítero Sectorial', 'Tesorero Sectorial', 'Contralor Sectorial'])) {
            return $u->sector_id && isset($record->sector_id) && $record->sector_id == $u->sector_id;
        } elseif ($u->hasRole('Supervisor Distrital')) {
            return $u->district_id && isset($record->district_id) && $record->district_id == $u->district_id;
        } elseif ($u->hasAnyRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional'])) {
            return $u->region_id && isset($record->region_id) && $record->region_id == $u->region_id;
        } elseif ($u->hasAnyRole(['Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional'])) {
            return true; // Los usuarios nacionales pueden acceder a todos los registros de su contabilidad
        }

        return false;
    }

    /**
     * Método auxiliar para verificar si el usuario puede ver registros de cierta ubicación geográfica
     */
    public function canAccessGeographicLocation(int $sectorId = null, int $districtId = null, int $regionId = null): bool
    {
        $u = auth()->user();
        if (!$u) return false;

        if ($u->hasAnyRole(['Presbítero Sectorial', 'Tesorero Sectorial', 'Contralor Sectorial'])) {
            return $u->sector_id && $sectorId == $u->sector_id;
        } elseif ($u->hasRole('Supervisor Distrital')) {
            return $u->district_id && $districtId == $u->district_id;
        } elseif ($u->hasAnyRole(['Superintendente Regional', 'Tesorero Regional', 'Contralor Regional'])) {
            return $u->region_id && $regionId == $u->region_id;
        } elseif ($u->hasAnyRole(['Obispo Presidente', 'Tesorero Nacional', 'Contralor Nacional'])) {
            return true; // Los nacionales pueden ver todo
        }

        return false;
    }
}