<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ListaAsistenciaExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $filas) {}

    public function collection(): Collection
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return [
            'Fecha', 'Número de empleado', 'Nombre', 'Área', 'Cargo', 'Sección',
            'Estatus nómina', 'Tipo de permiso', 'Tipo de incapacidad', 'Folio incapacidad',
        ];
    }

    public function map($fila): array
    {
        return [
            $fila->fecha->format('Y-m-d'),
            $fila->clave,
            $fila->nombre_completo,
            $fila->area,
            $fila->cargo,
            $fila->seccion,
            $fila->estatus_nomina,
            $fila->tipo_permiso,
            $fila->tipo_incapacidad,
            $fila->folio_incapacidad,
        ];
    }
}
