<?php

namespace App\Exports;

use App\Models\Pastor;
use App\Models\Region;
use App\Models\District;
use App\Models\Sector;
use Illuminate\Support\Collection;
use App\Helpers\EstadisticasPastoresHelper;
use App\Helpers\EstadisticasIglesiasHelper;
use App\Helpers\EstadisticasMembresiaHelper;
use App\Helpers\CuadernoElectoralHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PastoresExportService
{
    public static function handle(array $data)
    {
        $tipo = $data['tipo_listado'] ?? 'pastores_registrados';

        return match ($tipo) {
            'pastores_registrados' => self::exportPastoresRegistrados($data),
            'estadisticas'         => self::exportEstadisticas($data),
            'cuaderno_electoral'   => self::exportCuadernoElectoral($data),
            default                => throw new \Exception('Tipo de listado no válido'),
        };
    }

    public static function exportPastoresRegistrados(array $data)
    {
        $user = auth()->user();

        // 📌 Corregido para considerar campos vacíos
        $regionId = !empty($data['region_id']) ? $data['region_id'] : $user->region_id;
        $districtId = !empty($data['district_id']) ? $data['district_id'] : $user->district_id;
        $sectorId = !empty($data['sector_id']) ? $data['sector_id'] : $user->sector_id;

        // 📌 Preparar nombres para encabezado
        $regionName = $regionId ? Region::find($regionId)?->name ?? 'TODOS' : 'TODOS';
        $districtName = $districtId ? District::find($districtId)?->name ?? 'TODOS' : 'TODOS';
        $sectorName = $sectorId ? Sector::find($sectorId)?->name ?? 'TODOS' : 'TODOS';

        
        // 📌 Asegurar relaciones necesarias
        $query = Pastor::with([
            'church',
            'currentMinistry.church',
            'currentMinistry.income',
            'currentMinistry.type',
            'currentMinistry.licence',
            'currentMinistry.level',
            'currentMinistry.courseType',
            'currentMinistry.positionType',
            'currentMinistry.currentPosition',
        ]);

        // ✅ Corregido para evitar errores si no vienen los filtros
        if (!empty($data['region_id'])) {
            $query->where('region_id', $data['region_id']);
        }

        if (!empty($data['district_id'])) {
            $query->where('district_id', $data['district_id']);
        }

        if (!empty($data['sector_id'])) {
            $query->where('sector_id', $data['sector_id']);
        }

        $records = $query->get();


        // Encabezados traducidos
        $translated = fn ($col) => match ($col) {
            'region_id' => 'Región',
            'district_id' => 'Distrito',
            'sector_id' => 'Sector',
            'name' => 'Nombre',
            'lastname' => 'Apellido',
            'number_cedula' => 'Cédula',
            'email' => 'Correo Electrónico',
            'phone_mobile' => 'Teléfono Móvil',
            'phone_house' => 'Teléfono de Habitación',
            'career' => 'Profesión',
            'church_name' => 'Iglesia Asignada',
            'birthdate' => 'Fecha de Nacimiento',
            'birthplace' => 'Lugar de Nacimiento',
            'baptism_date' => 'Fecha de Bautismo',
            'start_date_ministry' => 'Inicio del Ministerio',
            'how_work' => '¿Cómo Trabaja?',
            'other_studies' => 'Otros Estudios',
            'social_security' => 'Seguro Social',
            'housing_policy' => 'Política Habitacional',
            'other_work' => 'Otro Trabajo',
            'address' => 'Dirección',
            'code_pastor' => 'Código de Pastor',
            'pastor_income_id' => 'Ingreso Pastoral',
            'pastor_type_id' => 'Tipo de Pastor',
            'pastor_licence_id' => 'Licencia Pastoral',
            'pastor_level_id' => 'Nivel Ministerial',
            'course_type_id' => 'Curso IBLC',
            'position_type_id' => 'Tipo de Cargo',
            'current_position_id' => 'Cargo Actual',
            'appointment' => 'Nombramiento',
            'abisop' => 'ABISOP',
            'iblc' => 'IBLC',
            'promotion_year' => 'Año de Promoción',
            'promotion_number' => 'Número de Promoción',
            default => ucwords(str_replace('_', ' ', $col)),
        };

        $headings = array_merge(['#'], array_map($translated, $data['columns']));
        $rows = [];

        foreach ($records as $index => $pastor) {
            $ministry = $pastor->currentMinistry;
            $row = [$index + 1];

            foreach ($data['columns'] as $column) {
                if ($column === 'pastor_level_vip_id') continue;

                $value = match ($column) {
                    'region_id'   => optional($pastor->region)->name,
                    'district_id' => optional($pastor->district)->name,
                    'sector_id'   => optional($pastor->sector)->name,
                    'church_name'         => optional($ministry?->church)->name ?? optional($pastor->church)->name,
                    'social_security',
                    'housing_policy',
                    'other_work',
                    'abisop',
                    'iblc',
                    'appointment'         => $ministry?->{$column} ? 'Sí' : 'No',
                    'birthdate',
                    'baptism_date'        => optional($pastor->{$column})?->format('d/m/Y'),
                    'start_date_ministry' => optional($pastor->start_date_ministry)?->format('d/m/Y'),
                    'pastor_income_id'    => optional($ministry?->income)?->name,
                    'pastor_type_id'      => optional($ministry?->type)?->name,
                    'pastor_licence_id'   => optional($ministry?->licence)?->name,
                    'pastor_level_id'     => optional($ministry?->level)?->name,
                    'course_type_id'      => optional($ministry?->courseType)?->name,
                    'position_type_id'    => optional($ministry?->positionType)?->name,
                    'current_position_id' => optional($ministry?->currentPosition)?->name,
                    'promotion_year'      => $ministry?->promotion_year,
                    'promotion_number'    => $ministry?->promotion_number,
                    'address'             => $ministry?->address ?? $pastor->address,
                    default               => $pastor->{$column} ?? $ministry?->{$column},
                };

                $row[] = $value;
            }

            $rows[] = $row;
        }

        return self::exportFile(
            title: 'Listado de Pastores Registrados',
            rows: collect($rows),
            headings: $headings,
            filename: 'pastores_registrados',
            exportType: $data['export_type'] ?? 'pdf',
            region: $regionName,
            district: $districtName,
            sector: $sectorName,
        );
        
    }

    public static function exportEstadisticas(array $data)
    {
        $cuadroPastores = EstadisticasPastoresHelper::getCuadroPastores($data);
        $cuadroIglesias = EstadisticasIglesiasHelper::getCuadroIglesias($data);
        $cuadroMembresia = EstadisticasMembresiaHelper::getCuadroMembresia($data);

        $region   = $data['region_id']   ? Region::find($data['region_id'])->name ?? 'TODOS' : 'TODOS';
        $district = $data['district_id'] ? District::find($data['district_id'])->name ?? 'TODOS' : 'TODOS';
        $sector   = $data['sector_id']   ? Sector::find($data['sector_id'])->name ?? 'TODOS' : 'TODOS';

        $pdf = Pdf::loadView('exports.estadisticas-pastores', [
            'title' => 'Estadísticas de Pastores',
            'cuadroPastores' => $cuadroPastores,
            'cuadroIglesias' => $cuadroIglesias,
            'cuadroMembresia' => $cuadroMembresia,
            'region' => $region,
            'district' => $district,
            'sector' => $sector,
        ]);

        $filename = 'estadisticas_pastores_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }


    public static function exportCuadernoElectoral(array $data)
    {
        $rows = CuadernoElectoralHelper::getListado($data);
        $ubicacion = CuadernoElectoralHelper::getUbicacion($data);

        $pdf = Pdf::loadView('exports/cuaderno-electoral', [
            'rows' => $rows,
            'region' => $ubicacion['region'],
            'district' => $ubicacion['district'],
            'sector' => $ubicacion['sector'],
        ])->setPaper('letter', 'landscape');

        $filename = 'cuaderno_electoral_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }

    private static function exportFile(
        string $title,
        Collection $rows,
        string $filename,
        string $exportType = 'pdf',
        ?array $headings = null,
        ?string $region = null,
        ?string $district = null,
        ?string $sector = null,
    )
    
    {
        $filename = $filename . '_' . now()->format('Ymd_His');

        if ($exportType === 'pdf') {
            $pdf = Pdf::loadView('exports.pastores-pdf', [
                'title' => $title,
                'rows' => $rows,
                'headings' => $headings ?? array_keys($rows->first() ?? []),
                'region' => $region,
                'district' => $district,
                'sector' => $sector,
            ]);            

            return response()->streamDownload(fn () => print($pdf->output()), $filename . '.pdf');
        }

        return Excel::download(new class($rows, $headings) implements FromCollection, WithHeadings {
            public function __construct(public $rows, public $headings) {}
            public function collection() { return $this->rows; }
            public function headings(): array { return $this->headings ?? array_keys($this->rows->first() ?? []); }
        }, $filename . '.xlsx');
    }
}