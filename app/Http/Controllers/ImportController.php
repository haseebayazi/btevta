<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class ImportController extends Controller
{
    public function showCandidateImport()
    {
        $this->authorize('import', Candidate::class);

        // Get all active trades with ID, code, and name for reference
        $trades = Trade::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return view('import.candidates', compact('trades'));
    }

    public function downloadTemplate()
    {
        $this->authorize('import', Candidate::class);

        $templatePath = storage_path('app/templates/btevta_candidate_import_template.xlsx');

        // Create template if it doesn't exist
        if (!file_exists($templatePath)) {
            $this->createTemplate();
        }

        return response()->download($templatePath, 'BTEVTA_Import_Template.xlsx');
    }

    public function importCandidates(Request $request)
    {
        $this->authorize('import', Candidate::class);

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
                    'email' => 'nullable|email|max:255',
                    'district' => 'required|string|max:100',
                    'trade_id' => 'required|exists:trades,id',
                ], [
                    'trade_id.required' => "Trade '{$data['trade_raw']}' was not found. Please use a valid Trade ID, Code, or Name.",
                    'trade_id.exists' => "Trade '{$data['trade_raw']}' does not exist. Please check the 'Trades List' sheet in the template.",
                ]);

                if ($validator->fails()) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                try {
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
                        'trade_id' => $data['trade_id'],
                        'status' => 'new',
                    ];

                    // AUDIT FIX: Assign imported candidates to user's campus/oep based on role
                    $user = auth()->user();
                    if ($user->role === 'campus_admin' && $user->campus_id) {
                        $candidateData['campus_id'] = $user->campus_id;
                    } elseif ($user->role === 'oep' && $user->oep_id) {
                        $candidateData['oep_id'] = $user->oep_id;
                    }

                    // Track who created the record
                    $candidateData['created_by'] = $user->id;

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
        // Get the trade code/id from column L (index 11)
        $tradeValue = trim($row[11] ?? '');

        // Try to resolve trade - supports ID, Code, or Name (case-insensitive)
        $tradeId = null;
        $tradeCode = null;
        if (!empty($tradeValue)) {
            $trade = null;

            if (is_numeric($tradeValue)) {
                // It's likely a trade ID
                $trade = Trade::find((int)$tradeValue);
            }

            if (!$trade) {
                // Try exact code match (case-insensitive)
                $trade = Trade::whereRaw('LOWER(code) = ?', [strtolower($tradeValue)])->first();
            }

            if (!$trade) {
                // Try exact name match (case-insensitive)
                $trade = Trade::whereRaw('LOWER(name) = ?', [strtolower($tradeValue)])->first();
            }

            if (!$trade) {
                // Try partial name match (case-insensitive)
                // Escape LIKE wildcards to prevent unexpected matches from user input
                $escapedValue = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower($tradeValue));
                $trade = Trade::whereRaw('LOWER(name) LIKE ?', ['%' . $escapedValue . '%'])->first();
            }

            if ($trade) {
                $tradeId = $trade->id;
                $tradeCode = $trade->code;
            }
        }

        return [
            'btevta_id' => $row[0] ?? null,
            'cnic' => $row[1] ?? null,
            'name' => $row[2] ?? null,
            'father_name' => $row[3] ?? null,
            'date_of_birth' => $row[4] ?? null,
            'gender' => strtolower(trim($row[5] ?? '')),
            'phone' => $row[6] ?? null,
            'email' => $row[7] ?? null,
            'address' => $row[8] ?? null,
            'district' => $row[9] ?? null,
            'tehsil' => $row[10] ?? null,
            'trade_id' => $tradeId,
            'trade_code' => $tradeCode,
            'trade_raw' => $tradeValue, // Keep original for error reporting
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

            // =============== SHEET 1: Candidates (Main Import Sheet) ===============
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
                'Trade (Code, ID, or Name)',
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
            $sheet->getColumnDimension('E')->setWidth(25);
            $sheet->getColumnDimension('F')->setWidth(22);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(20);
            $sheet->getColumnDimension('I')->setWidth(25);
            $sheet->getColumnDimension('J')->setWidth(15);
            $sheet->getColumnDimension('K')->setWidth(15);
            $sheet->getColumnDimension('L')->setWidth(25);

            // Get a real trade for sample data
            $sampleTrade = Trade::where('is_active', true)->first();
            $sampleTradeValue = $sampleTrade ? $sampleTrade->code : '(See Trades List sheet)';

            // Add sample data row with note
            $sampleData = [
                'BTV-2025-00001',       // BTEVTA ID
                '3520112345678',        // CNIC (realistic Pakistani CNIC)
                'Muhammad Ali',         // Full Name
                'Muhammad Akbar',       // Father Name
                '2000-01-15',           // Date of Birth
                'male',                 // Gender
                '03001234567',          // Phone
                'ali@example.com',      // Email
                'House 123, Street 5',  // Address
                'Lahore',               // District
                'Gulberg',              // Tehsil
                $sampleTradeValue,      // Trade Code
            ];

            foreach ($sampleData as $index => $value) {
                $sheet->setCellValueByColumnAndRow($index + 1, 2, $value);
            }

            // Format sample row
            $sheet->getStyle('A2:L2')->getFont()->setItalic(true);
            $sheet->getStyle('A2:L2')->getFont()->getColor()->setARGB('FF666666');

            // Add a note in row 3
            $sheet->setCellValue('A3', '↑ Sample row above - delete before importing. See "Trades List" sheet for available trade codes/IDs.');
            $sheet->mergeCells('A3:L3');
            $sheet->getStyle('A3')->getFont()->setItalic(true);
            $sheet->getStyle('A3')->getFont()->setBold(true);
            $sheet->getStyle('A3')->getFont()->getColor()->setARGB('FFFF6600');

            // Add instructions in comment
            $sheet->getComment('A1')->setAuthor('BTEVTA System');
            $sheet->getComment('A1')->getText()->createTextRun(
                "Instructions:\n" .
                "1. Fill in candidate information starting from row 4\n" .
                "2. Delete the sample row (row 2) before importing\n" .
                "3. CNIC must be exactly 13 digits (no dashes)\n" .
                "4. Date format must be YYYY-MM-DD\n" .
                "5. Gender must be: male, female, or other\n" .
                "6. Trade can be: Trade Code, Trade ID, or Trade Name\n" .
                "7. See 'Trades List' sheet for available trades\n" .
                "8. All required fields must be filled"
            );

            // =============== SHEET 2: Trades List (Reference Sheet) ===============
            $tradesSheet = $spreadsheet->createSheet();
            $tradesSheet->setTitle('Trades List');

            // Add header
            $tradesSheet->setCellValue('A1', 'ID');
            $tradesSheet->setCellValue('B1', 'Trade Code');
            $tradesSheet->setCellValue('C1', 'Trade Name');
            $tradesSheet->setCellValue('D1', 'Category');
            $tradesSheet->setCellValue('E1', 'Duration (Months)');

            // Format header
            $tradesHeaderStyle = $tradesSheet->getStyle('A1:E1');
            $tradesHeaderStyle->getFont()->setBold(true);
            $tradesHeaderStyle->getFont()->setSize(12);
            $tradesHeaderStyle->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF70AD47'); // Green background
            $tradesHeaderStyle->getFont()->setColor(
                new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF')
            );

            // Add all active trades
            $trades = Trade::where('is_active', true)->orderBy('name')->get();
            $rowNum = 2;
            foreach ($trades as $trade) {
                $tradesSheet->setCellValue('A' . $rowNum, $trade->id);
                $tradesSheet->setCellValue('B' . $rowNum, $trade->code);
                $tradesSheet->setCellValue('C' . $rowNum, $trade->name);
                $tradesSheet->setCellValue('D' . $rowNum, $trade->category ?? 'N/A');
                $tradesSheet->setCellValue('E' . $rowNum, $trade->duration_months ?? 'N/A');
                $rowNum++;
            }

            // Set column widths for trades sheet
            $tradesSheet->getColumnDimension('A')->setWidth(10);
            $tradesSheet->getColumnDimension('B')->setWidth(15);
            $tradesSheet->getColumnDimension('C')->setWidth(30);
            $tradesSheet->getColumnDimension('D')->setWidth(20);
            $tradesSheet->getColumnDimension('E')->setWidth(18);

            // Add note at the bottom
            $noteRow = $rowNum + 1;
            $tradesSheet->setCellValue('A' . $noteRow, 'Note: You can use ID, Code, or Name in the "Trade" column of Candidates sheet.');
            $tradesSheet->mergeCells('A' . $noteRow . ':E' . $noteRow);
            $tradesSheet->getStyle('A' . $noteRow)->getFont()->setItalic(true);
            $tradesSheet->getStyle('A' . $noteRow)->getFont()->getColor()->setARGB('FF0066CC');

            // Set active sheet back to Candidates
            $spreadsheet->setActiveSheetIndex(0);

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
            Log::error('Failed to create import template: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Force regenerate the import template (for admin use)
     */
    public function regenerateTemplate()
    {
        $this->authorize('import', Candidate::class);

        // Delete existing template
        $templatePath = storage_path('app/templates/btevta_candidate_import_template.xlsx');
        if (file_exists($templatePath)) {
            unlink($templatePath);
        }

        if ($this->createTemplate()) {
            return redirect()->back()->with('success', 'Import template regenerated successfully with latest trades.');
        }

        return redirect()->back()->with('error', 'Failed to regenerate template.');
    }
}