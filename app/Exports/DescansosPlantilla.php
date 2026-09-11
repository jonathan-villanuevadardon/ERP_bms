<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DescansosPlantilla implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings
{
    public function collection(): Collection
    {
        return collect([
            [116, 'por_periodo', now()->format('Y-m-d'), now()->addDay()->format('Y-m-d'), 'Ejemplo; elimina esta fila'],
        ]);
    }

    public function headings(): array
    {
        return ['numero_empleado', 'tipo', 'fecha_inicio', 'fecha_fin', 'observaciones'];
    }

    public function columnFormats(): array
    {
        return ['C' => NumberFormat::FORMAT_TEXT, 'D' => NumberFormat::FORMAT_TEXT];
    }
}
