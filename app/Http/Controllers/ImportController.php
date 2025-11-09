<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class ImportController extends Controller
{
    public function showCandidateImport()
    {
        return view('import.candidates');
    }

    public function downloadTemplate()
    {
        $templatePath = storage_path('app/templates/btevta_candidate_import_template.xlsx');
        
        // Create template if it doesn't exist
        if (!file_exists($templatePath)) {
            $this->createTemplate();
        }
        
        return response()->download($templatePath, 'BTEVTA_Import_Template.xlsx');
    }

    public function importCandidates(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Remove header row
            $header = array_shift($rows);
            
            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and Excel starts at 1

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = $this->mapRowToData($row);
                
                // Validate data
                $validator = Validator::make($data, [
                    'btevta_id' => 'required|unique:candidates,btevta_id',
                    'cnic' => 'required|digits:13|unique:candidates,cnic',
                    'name' => 'required|string|max:255',
                    'father_name' => 'required|string|max:255',
                    'date_of_birth' => 'required|date|before:today',
                    'gender' => 'required|in:male,female,other',
                    'phone' => 'required|string|max:20',
                    'district' => 'required|string|max:100',
                    'trade_code' => 'required|exists:trades,code',
                ]);

                if ($validator->fails()) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                try {
                    // Get trade by code
                    $trade = Trade::where('code', $data['trade_code'])->first();
                    
                    $candidateData = [
                        'btevta_id' => $data['btevta_id'],
                        'cnic' => $data['cnic'],
                        'name' => $data['name'],
                        'father_name' => $data['father_name'],
                        'date_of_birth' => $data['date_of_birth'],
                        'gender' => $data['gender'],
                        'phone' => $data['phone'],
                        'email' => $data['email'] ?? null,
                        'address' => $data['address'] ?? 'N/A',
                        'district' => $data['district'],
                        'tehsil' => $data['tehsil'] ?? null,
                        'trade_id' => $trade->id,
                        'status' => 'listed',
                    ];

                    $candidate = Candidate::create($candidateData);
                    
                    activity()
                        ->performedOn($candidate)
                        ->log('Candidate imported from bulk import');

                    $imported++;
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Failed to create candidate - " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Import completed! Imported: {$imported}, Skipped: {$skipped}";
            
            if (!empty($errors)) {
                session()->flash('import_errors', $errors);
            }

            return redirect()->route('import.candidates.form')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error processing file: ' . $e->getMessage());
        }
    }

    private function mapRowToData($row)
    {
        return [
            'btevta_id' => $row[0] ?? null,
            'cnic' => $row[1] ?? null,
            'name' => $row[2] ?? null,
            'father_name' => $row[3] ?? null,
            'date_of_birth' => $row[4] ?? null,
            'gender' => $row[5] ?? null,
            'phone' => $row[6] ?? null,
            'email' => $row[7] ?? null,
            'address' => $row[8] ?? null,
            'district' => $row[9] ?? null,
            'tehsil' => $row[10] ?? null,
            'trade_code' => $row[11] ?? null,
        ];
    }

    /**
     * CREATE TEMPLATE FILE
     * 
     * This method creates the import template if it doesn't exist.
     * It's automatically called when downloading template.
     */
    private function createTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Candidates');
            
            // Set headers
            $headers = [
                'BTEVTA ID',
                'CNIC (13 digits)',
                'Full Name',
                'Father Name',
                'Date of Birth (YYYY-MM-DD)',
                'Gender (male/female/other)',
                'Phone Number',
                'Email (optional)',
                'Address',
                'District',
                'Tehsil (optional)',
                'Trade Code',
            ];
            
            foreach ($headers as $index => $header) {
                $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
            }
            
            // Format header row
            $headerStyle = $sheet->getStyle('A1:L1');
            $headerStyle->getFont()->setBold(true);
            $headerStyle->getFont()->setSize(12);
            $headerStyle->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4'); // Blue background
            
            // Set font color to white for headers
            $headerStyle->getFont()->setColor(
                new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF')
            );
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(20);
            $sheet->getColumnDimension('I')->setWidth(25);
            $sheet->getColumnDimension('J')->setWidth(15);
            $sheet->getColumnDimension('K')->setWidth(15);
            $sheet->getColumnDimension('L')->setWidth(15);
            
            // Add sample data row
            $sampleData = [
                'BTEVTA001',           // BTEVTA ID
                '1234567890123',       // CNIC
                'John Doe',            // Full Name
                'Ahmed Doe',           // Father Name
                '2000-01-15',          // Date of Birth
                'male',                // Gender
                '03001234567',         // Phone
                'john@example.com',    // Email
                '123 Main Street',     // Address
                'Lahore',              // District
                'Central',             // Tehsil
                'TRADE001',            // Trade Code
            ];
            
            foreach ($sampleData as $index => $value) {
                $sheet->setCellValueByColumnAndRow($index + 1, 2, $value);
            }
            
            // Format sample row
            $sheet->getStyle('A2:L2')->getFont()->setItalic(true);
            
            // Add instructions in comment
            $sheet->getComment('A1')->setAuthor('BTEVTA System');
            $sheet->getComment('A1')->getText()->createTextRun(
                "Instructions:\n" .
                "1. Fill in candidate information in rows below the header\n" .
                "2. CNIC must be exactly 13 digits\n" .
                "3. Date format must be YYYY-MM-DD\n" .
                "4. Gender must be: male, female, or other\n" .
                "5. Trade Code must exist in the system\n" .
                "6. All required fields must be filled"
            );
            
            // Create directory if it doesn't exist
            $templateDir = storage_path('app/templates');
            if (!is_dir($templateDir)) {
                mkdir($templateDir, 0755, true);
            }
            
            // Save the spreadsheet
            $writer = new Xlsx($spreadsheet);
            $templatePath = $templateDir . '/btevta_candidate_import_template.xlsx';
            $writer->save($templatePath);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to create import template: ' . $e->getMessage());
            return false;
        }
    }
}