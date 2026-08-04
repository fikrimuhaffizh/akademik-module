<?php

namespace Modules\Akademik\Exports;

use Modules\Akademik\Services\MahasiswaService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MahasiswaExport implements FromQuery, WithChunkReading, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function query()
    {
        return app(MahasiswaService::class)->getFilteredQuery($this->filters);
    }

    public function headings(): array
    {
        return [
            'No',
            'NIM',
            'Nama',
            'Email',
            'No HP',
            'Program Studi',
            'Angkatan',
            'Status',
            'Jenis Masuk',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        $prodiNama = $row->prodi ? $row->prodi->nama : '-';
        $statusLabel = ucfirst($row->status ?? '-');
        $jenisMasuk = $row->jenis_masuk ?? '-';

        return [
            $no,
            $row->nim ?? '-',
            $row->nama ?? '-',
            $row->email ?? '-',
            $row->no_hp ?? '-',
            $prodiNama,
            $row->angkatan ?? '-',
            $statusLabel,
            $jenisMasuk,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 15,
            'C' => 30,
            'D' => 30,
            'E' => 20,
            'F' => 30,
            'G' => 12,
            'H' => 15,
            'I' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }
}