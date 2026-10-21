<?php

namespace Tests\Unit;

use App\Exports\ListaAsistenciaExport;
use App\Exports\ListaAsistenciaMatrizExport;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ListaAsistenciaExportTest extends TestCase
{
    public function test_base_de_datos_conserva_su_formato_actual(): void
    {
        $fila = $this->fila(100, 'Ana Pérez', '2026-10-01', 'ASISTENCIA');
        $export = new ListaAsistenciaExport(collect([$fila]));

        $this->assertSame([
            'Fecha', 'Número de empleado', 'Nombre', 'Área', 'Cargo', 'Sección',
            'Estatus nómina', 'Tipo de permiso', 'Tipo de incapacidad', 'Folio incapacidad',
        ], $export->headings());
        $this->assertSame([
            '2026-10-01', 100, 'Ana Pérez', 'Operaciones', 'Operador', 'A',
            'ASISTENCIA', null, null, null,
        ], $export->map($fila));
    }

    public function test_lista_genera_matriz_y_totales_por_estatus(): void
    {
        $export = new ListaAsistenciaMatrizExport(collect([
            $this->fila(100, 'Ana Pérez', '2026-10-01', 'ASISTENCIA'),
            $this->fila(100, 'Ana Pérez', '2026-10-02', 'FALTA'),
            $this->fila(200, 'Luis López', '2026-10-01', 'VACACIONES'),
            $this->fila(200, 'Luis López', '2026-10-02', 'VACACIONES'),
        ]), '2026-10-01', '2026-10-02');

        $this->assertSame([
            'Número de empleado', 'Nombre', 'Área', 'Cargo', 'Sección',
            '01/10/2026', '02/10/2026',
            'Total INCAPACIDAD', 'Total VACACIONES', 'Total PERMISO',
            'Total DESCANSO', 'Total ASISTENCIA', 'Total FALTA',
        ], $export->headings());
        $this->assertSame([
            [100, 'Ana Pérez', 'Operaciones', 'Operador', 'A', 'ASISTENCIA', 'FALTA', 0, 0, 0, 0, 1, 1],
            [200, 'Luis López', 'Operaciones', 'Operador', 'A', 'VACACIONES', 'VACACIONES', 0, 2, 0, 0, 0, 0],
        ], $export->collection()->all());
    }

    public function test_lista_se_puede_generar_como_archivo_xlsx(): void
    {
        $export = new ListaAsistenciaMatrizExport(collect([
            $this->fila(100, 'Ana Pérez', '2026-10-01', 'ASISTENCIA'),
        ]), '2026-10-01', '2026-10-01');

        $contenido = Excel::raw($export, ExcelWriter::XLSX);

        $this->assertStringStartsWith('PK', $contenido);
        $this->assertGreaterThan(1000, strlen($contenido));
    }

    private function fila(int $clave, string $nombre, string $fecha, string $estatus): object
    {
        return (object) [
            'clave' => $clave,
            'nombre_completo' => $nombre,
            'area' => 'Operaciones',
            'cargo' => 'Operador',
            'seccion' => 'A',
            'fecha' => CarbonImmutable::createFromFormat('Y-m-d', $fecha),
            'estatus_nomina' => $estatus,
            'tipo_permiso' => null,
            'tipo_incapacidad' => null,
            'folio_incapacidad' => null,
        ];
    }
}
