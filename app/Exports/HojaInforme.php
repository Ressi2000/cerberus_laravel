<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/** Una hoja del informe de Cerberus Bot (ver InformeAsistenteExport). */
class HojaInforme implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(private string $titulo, private array $filas, private array $negritas = [], private bool $esTabla = false)
    {
    }

    public function array(): array
    {
        return $this->filas;
    }

    public function title(): string
    {
        return $this->titulo;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $hoja = $e->sheet->getDelegate();
                foreach ($this->negritas as $fila) {
                    $hoja->getStyle("A{$fila}:Z{$fila}")->getFont()->setBold(true);
                }
                if ($this->esTabla && $this->filas) {
                    $ultima = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(1, count($this->filas[0])));
                    $hoja->getStyle("A1:{$ultima}1")->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
                    $hoja->getStyle("A1:{$ultima}1")->getFont()->getColor()->setRGB('FFFFFF');
                    $hoja->setAutoFilter("A1:{$ultima}" . count($this->filas));
                    $hoja->freezePane('A2');
                }
            },
        ];
    }
}
