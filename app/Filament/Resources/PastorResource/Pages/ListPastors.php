<?php

namespace App\Filament\Resources\PastorResource\Pages;

use App\Filament\Resources\PastorResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListPastors extends ListRecords
{
    protected static string $resource = PastorResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        $nationalRoles = [
            'Administrador', 'Obispo Presidente', 'Secretario Nacional',
            'Tesorero Nacional', 'Contralor Nacional', 'Inspector Nacional',
            'Directivo Nacional',
        ];

        $regionalRoles = [
            'Superintendente Regional', 'Secretario Regional',
            'Tesorero Regional', 'Contralor Regional', 'Inspector Regional',
            'Directivo Regional',
        ];

        $districtRoles = ['Supervisor Distrital'];

        $sectorRoles = [
            'Presbítero Sectorial', 'Secretario Sectorial', 'Tesorero Sectorial',
            'Contralor Sectorial', 'Directivo Sectorial',
        ];

        $isNational = $user->hasAnyRole($nationalRoles);
        $isRegional = $user->hasAnyRole($regionalRoles);
        $isDistrict = $user->hasAnyRole($districtRoles);
        $isSector   = $user->hasAnyRole($sectorRoles);

        $actions = [];

        if ($isNational || $isRegional || $isDistrict || $isSector) {
            $actions[] = Actions\CreateAction::make()->label('Nuevo Pastor');

            $actions[] = Action::make('exportPastors')
                ->label('Generar Listados')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('tipo_listado')
                        ->label('Tipo de Listado')
                        ->native(false)
                        ->options([
                            'pastores_registrados' => 'Pastores Registrados',
                            'estadisticas' => 'Estadísticas',
                            'cuaderno_electoral' => 'Cuaderno Electoral',
                        ])
                        ->required()
                        ->reactive(),

                    Forms\Components\Select::make('region_id')
                        ->label('Filtrar por Región')
                        ->relationship('region', 'name')
                        ->searchable()
                        ->native(false)
                        ->placeholder('Todas')
                        ->default(($isRegional || $isDistrict || $isSector) ? $user->region_id : null)
                        ->disabled($isRegional || $isDistrict || $isSector)
                        ->requiredIf('tipo_listado', 'cuaderno_electoral')
                        ->helperText(fn ($get) =>
                            $get('tipo_listado') === 'cuaderno_electoral' ? 'Debe seleccionar al menos una Región.' : null
                        ),
                    
                    
                    


                    Forms\Components\Select::make('district_id')
                        ->label('Filtrar por Distrito')
                        ->searchable()
                        ->native(false)
                        ->placeholder('Todos')
                        ->options(fn ($get) =>
                            $get('region_id')
                                ? \App\Models\District::where('region_id', $get('region_id'))->pluck('name', 'id')
                                : []
                        )
                        ->default(($isDistrict || $isSector) ? $user->district_id : null)
                        ->disabled($isDistrict || $isSector)
                        ->reactive(),

                    Forms\Components\Select::make('sector_id')
                        ->label('Filtrar por Sector')
                        ->searchable()
                        ->native(false)
                        ->placeholder('Todos')
                        ->options(fn ($get) =>
                            $get('district_id')
                                ? \App\Models\Sector::where('district_id', $get('district_id'))->pluck('name', 'id')
                                : []
                        )
                        ->default($isSector ? $user->sector_id : null)
                        ->disabled($isSector),

                    Forms\Components\Toggle::make('select_all')
                        ->label('Seleccionar todas las columnas')
                        ->hidden(fn ($get) => $get('tipo_listado') !== 'pastores_registrados')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('columns', $state ? array_keys(self::getAvailableColumns()) : []);
                        }),

                    Forms\Components\CheckboxList::make('columns')
                        ->label('Columnas a Mostrar')
                        ->options(self::getAvailableColumns())
                        ->columns(2)
                        ->requiredIf('tipo_listado', 'pastores_registrados')
                        ->hidden(fn ($get) => $get('tipo_listado') !== 'pastores_registrados')
                        ->reactive(),

                    Forms\Components\Radio::make('export_type')
                        ->label('Tipo de archivo')
                        ->options(fn ($get) => match ($get('tipo_listado')) {
                            'estadisticas', 'cuaderno_electoral' => ['pdf' => 'PDF'],
                            default => ['pdf' => 'PDF', 'excel' => 'Excel'],
                        })
                        ->default('pdf')
                        ->inline()
                        ->required()
                        ->reactive(),
                    
                    
                ])
                ->modalSubmitActionLabel('Descargar')
                ->action(function (array $data) {
                    return \App\Exports\PastoresExportService::handle($data);
                });

        }

        return $actions;
    }

    protected static function getAvailableColumns(): array
    {
        return [
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
            'birthdate' => 'Fecha de Nacimiento',
            'birthplace' => 'Lugar de Nacimiento',
            'baptism_date' => 'Fecha de Bautismo',
            'church_name' => 'Iglesia Asignada',
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
        ];
    }
}