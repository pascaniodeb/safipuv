<?php

namespace App\Exports;

use App\Models\Pastor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Auth;

class PastorsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    /**
     * Obtener los datos filtrados según el rol del usuario.
     */
    public function collection()
    {
        $query = Pastor::with([
            'region', 'district', 'sector', 'state', 'city',
            'gender', 'nationality', 'bloodType', 'academicLevel',
            'maritalStatus', 'housingType'
        ]);

        // 🔹 Si el usuario tiene rol nacional, puede ver todos los pastores.
        if ($this->user->hasAnyRole([
            'Administrador', 'Obispo Presidente', 'Secretario Nacional', 
            'Tesorero Nacional', 'Contralor Nacional', 'Inspector Nacional', 
            'Directivo Nacional'
        ])) {
            return $query->get();
        }

        // 🔹 Si el usuario tiene rol regional, solo ve los pastores de su región.
        if ($this->user->hasRole('Superintendente Regional')) {
            return $query->where('region_id', $this->user->region_id)->get();
        }

        // 🔹 Si el usuario tiene rol distrital, solo ve los pastores de su distrito.
        if ($this->user->hasRole('Supervisor Distrital')) {
            return $query->where('district_id', $this->user->district_id)->get();
        }

        // 🔹 Si el usuario tiene rol sectorial, solo ve los pastores de su sector.
        if ($this->user->hasAnyRole([
            'Presbítero Sectorial', 'Secretario Sectorial', 
            'Tesorero Sectorial', 'Contralor Sectorial', 'Directivo Sectorial'
        ])) {
            return $query->where('sector_id', $this->user->sector_id)->get();
        }

        // Si el usuario no tiene un rol válido, retorna una colección vacía
        return collect([]);
    }

    /**
     * Asignar encabezados a las columnas del archivo Excel.
     */
    public function headings(): array
    {
        return [
            "ID", "Nombre", "Apellido", "Cédula", "Correo Electrónico",
            "Móvil", "Teléfono Casa", "Fecha de Nacimiento", "Lugar de Nacimiento",
            "Fecha de Bautismo", "Quién Bautizó", "Fecha de Inicio en el Ministerio",
            "Carrera", "Nivel Académico", "Otros Estudios", "Cómo Trabaja",
            "Otros Trabajos", "Seguridad Social", "Política Habitacional",
            "Tipo de Vivienda", "Dirección",
            "Región", "Distrito", "Sector", "Estado", "Ciudad",
            "Género", "Nacionalidad", "Tipo de Sangre", "Estado Civil",
            "Fecha de Creación", "Última Actualización"
        ];
    }

    /**
     * Mapear los datos en el formato correcto para Excel.
     */
    public function map($pastor): array
    {
        return [
            $pastor->id,
            $pastor->name,
            $pastor->lastname,
            $pastor->number_cedula,
            $pastor->email,
            $pastor->phone_mobile ?? "No tiene",
            $pastor->phone_house ?? "No tiene",
            $pastor->birthdate,
            $pastor->birthplace ?? "No especificado",
            $pastor->baptism_date ?? "No especificado",
            $pastor->who_baptized ?? "No especificado",
            $pastor->start_date_ministry ?? "No especificado",
            $pastor->career ?? "No especificado",
            optional($pastor->academicLevel)->name ?? "No especificado",
            $pastor->other_studies ?? "No especificado",
            $pastor->how_work ?? "No especificado",
            $pastor->other_work ? "Sí" : "No",
            $pastor->social_security ? "Sí" : "No",
            $pastor->housing_policy ? "Sí" : "No",
            optional($pastor->housingType)->name ?? "No especificado",
            $pastor->address ?? "No especificado",
            optional($pastor->region)->name ?? "No especificado",
            optional($pastor->district)->name ?? "No especificado",
            optional($pastor->sector)->name ?? "No especificado",
            optional($pastor->state)->name ?? "No especificado",
            optional($pastor->city)->name ?? "No especificado",
            optional($pastor->gender)->name ?? "No especificado",
            optional($pastor->nationality)->name ?? "No especificado",
            optional($pastor->bloodType)->name ?? "No especificado",
            optional($pastor->maritalStatus)->name ?? "No especificado",
            $pastor->created_at,
            $pastor->updated_at,
        ];
    }
}