<?php

namespace App\Exports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CandidatesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Build query for export
     */
    public function query()
    {
        $query = Candidate::with(['batch', 'campus', 'trade', 'nextOfKin']);

        // Apply filters
        if (!empty($this->filters['search'])) {
            $query->search($this->filters['search']);
        }

        if (!empty($this->filters['status'])) {
            $query->byStatus($this->filters['status']);
        }

        if (!empty($this->filters['campus_id'])) {
            $query->byCampus($this->filters['campus_id']);
        }

        if (!empty($this->filters['batch_id'])) {
            $query->byBatch($this->filters['batch_id']);
        }

        return $query;
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'Application ID',
            'Name',
            'Father Name',
            'CNIC',
            'Phone',
            'Email',
            'Date of Birth',
            'Age',
            'Gender',
            'Address',
            'District',
            'Province',
            'Qualification',
            'Experience (Years)',
            'Campus',
            'Batch',
            'Trade',
            'Status',
            'Training Status',
            'Registration Date',
            'Next of Kin Name',
            'Next of Kin Relation',
            'Next of Kin Contact',
            'Created Date',
        ];
    }

    /**
     * Map data for export
     */
    public function map($candidate): array
    {
        return [
            $candidate->application_id,
            $candidate->name,
            $candidate->father_name,
            $candidate->formatted_cnic,
            $candidate->phone,
            $candidate->email,
            $candidate->date_of_birth ? $candidate->date_of_birth->format('d-m-Y') : '',
            $candidate->age,
            ucfirst($candidate->gender),
            $candidate->address,
            $candidate->district,
            $candidate->province,
            $candidate->qualification,
            $candidate->experience_years,
            $candidate->campus->name ?? '',
            $candidate->batch->batch_code ?? '',
            $candidate->trade->name ?? '',
            $candidate->status_label,
            $candidate->training_status_label ?? '',
            $candidate->registration_date ? $candidate->registration_date->format('d-m-Y') : '',
            $candidate->nextOfKin->name ?? '',
            $candidate->nextOfKin->relationship ?? '',
            $candidate->nextOfKin->phone ?? '',
            $candidate->created_at->format('d-m-Y H:i'),
        ];
    }

    /**
     * Style the export
     */
    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:X1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4472C4'],
            ],
        ]);

        // Add borders to all cells with data
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Freeze the first row
        $sheet->freezePane('A2');

        return [];
    }
}