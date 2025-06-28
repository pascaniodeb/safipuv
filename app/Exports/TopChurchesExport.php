<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TopChurchesExport implements FromView, ShouldAutoSize
{
    protected string $categoria;
    protected string $periodo;
    protected string $referencia;
    protected array $datos;

    public function __construct(string $categoria, string $periodo, string $referencia, array $datos)
    {
        $this->categoria = $categoria;
        $this->periodo = $periodo;
        $this->referencia = $referencia;
        $this->datos = $datos;
    }

    public function view(): View
    {
        return view('exports.top-200-churches', [
            'categoria'  => $this->categoria,
            'periodo'    => $this->periodo,
            'referencia' => $this->referencia,
            'datos'      => $this->datos,
        ]);
    }
}