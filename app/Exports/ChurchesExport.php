<?php

namespace App\Exports;

use App\Models\Church;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Auth;

class ChurchesExport implements FromCollection, WithHeadings, WithMapping
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
        $query = Church::with([
            'region', 'district', 'sector', 'state', 'city', 'categoryChurch', 'currentPastor'
        ]);

        // 🔹 Si el usuario tiene rol nacional, puede ver todas las iglesias.
        if ($this->user->hasAnyRole([
            'Administrador', 'Obispo Presidente', 'Secretario Nacional', 
            'Tesorero Nacional', 'Contralor Nacional', 'Inspector Nacional', 
            'Directivo Nacional'
        ])) {
            return $query->get();
        }

        // 🔹 Si el usuario tiene rol regional, solo ve las iglesias de su región.
        if ($this->user->hasRole('Superintendente Regional')) {
            return $query->where('region_id', $this->user->region_id)->get();
        }

        // 🔹 Si el usuario tiene rol distrital, solo ve las iglesias de su distrito.
        if ($this->user->hasRole('Supervisor Distrital')) {
            return $query->where('district_id', $this->user->district_id)->get();
        }

        // 🔹 Si el usuario tiene rol sectorial, solo ve las iglesias de su sector.
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
     * Definir los encabezados de las columnas en Excel.
     */
    public function headings(): array
    {
        return [
            "ID",
            "Nombre",
            "Código",
            "Fecha de Apertura",
            "Pastor Fundador",
            "Pastor Asignado",
            "Región", "Distrito", "Sector", "Estado", "Ciudad", "Dirección",
            "Pastor Actual", "Cédula Pastor", "Posición Actual",
            "Número de Adultos", "Número de Niños", "Bautizados",
            "Por Bautizar", "Llenos del Espíritu Santo", "Células",
            "Centros de Predicación", "Total Miembros",
            "Categoría Iglesia", "Legalizada", "Número RIF",
            "Profesionales", "Fecha de Creación", "Última Actualización"
        ];
    }

    /**
     * Mapear los datos de cada fila.
     */
    public function map($church): array
    {
        return [
            $church->id,
            $church->name,
            $church->code_church,
            $church->date_opening ?? "No especificado",
            $church->pastor_founding ?? "No especificado",
            optional($church->currentPastor)->name ?? 'No asignado',
            optional($church->region)->name ?? "No especificado",
            optional($church->district)->name ?? "No especificado",
            optional($church->sector)->name ?? "No especificado",
            optional($church->state)->name ?? "No especificado",
            optional($church->city)->name ?? "No especificado",
            $church->address ?? "No especificado",
            $church->pastor_current ?? "No especificado",
            $church->number_cedula ?? "No especificado",
            optional($church->currentPosition)->name ?? "No especificado",
            $church->adults,
            $church->children,
            $church->baptized,
            $church->to_baptize,
            $church->holy_spirit,
            $church->groups_cells,
            $church->centers_preaching,
            $church->members,
            optional($church->categoryChurch)->name ?? "No especificado",
            $church->legalized ? "Sí" : "No",
            $church->number_rif ?? "No especificado",
            $church->professionals ? "Sí" : "No",
            $church->created_at,
            $church->updated_at,
        ];
    }
}