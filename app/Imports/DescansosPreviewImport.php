<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DescansosPreviewImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        // Excel::toCollection devuelve las filas al controlador sin persistirlas.
    }
}
