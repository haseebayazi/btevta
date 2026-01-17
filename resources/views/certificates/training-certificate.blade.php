<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            background: #fff;
            padding: 0;
            margin: 0;
        }

        .certificate-container {
            width: 297mm;
            height: 210mm;
            padding: 20mm;
            position: relative;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .certificate-border {
            border: 8px solid #1e40af;
            border-radius: 15px;
            padding: 15mm;
            height: 100%;
            position: relative;
            background: white;
            box-shadow: inset 0 0 0 2px #3b82f6;
        }

        .certificate-inner-border {
            border: 2px solid #93c5fd;
            padding: 10mm;
            height: 100%;
            position: relative;
        }

        /* Header */
        .certificate-header {
            text-align: center;
            margin-bottom: 15mm;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
        }

        .organization-name {
            font-size: 32px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .organization-tagline {
            font-size: 14px;
            color: #64748b;
            font-style: italic;
            margin-bottom: 15px;
        }

        .certificate-title {
            font-size: 36px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 3px;
            border-bottom: 3px solid #3b82f6;
            display: inline-block;
            padding: 5px 40px;
        }

        /* Content */
        .certificate-content {
            text-align: center;
            margin: 20mm 0;
        }

        .awarded-text {
            font-size: 18px;
            color: #475569;
            margin-bottom: 10mm;
        }

        .candidate-name {
            font-size: 42px;
            font-weight: bold;
            color: #1e293b;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 2px solid #cbd5e1;
            display: inline-block;
            padding: 5px 30px;
        }

        .completion-text {
            font-size: 16px;
            color: #475569;
            line-height: 1.8;
            margin: 15mm 0;
            max-width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .trade-name {
            font-weight: bold;
            color: #1e40af;
            font-size: 18px;
        }

        .details-grid {
            display: table;
            width: 80%;
            margin: 10mm auto;
            border-collapse: collapse;
        }

        .details-row {
            display: table-row;
        }

        .details-label {
            display: table-cell;
            text-align: right;
            padding: 8px 20px;
            font-weight: bold;
            color: #64748b;
            font-size: 14px;
            width: 40%;
        }

        .details-value {
            display: table-cell;
            text-align: left;
            padding: 8px 20px;
            color: #1e293b;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Footer */
        .certificate-footer {
            position: absolute;
            bottom: 15mm;
            left: 15mm;
            right: 15mm;
        }

        .signatures {
            display: table;
            width: 100%;
            margin-top: 15mm;
        }

        .signature-block {
            display: table-cell;
            text-align: center;
            width: 33.33%;
            padding: 0 10px;
        }

        .signature-line {
            border-top: 2px solid #1e293b;
            margin-bottom: 8px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .signature-name {
            font-weight: bold;
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .signature-title {
            font-size: 12px;
            color: #64748b;
            font-style: italic;
        }

        .certificate-number {
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            margin-top: 10px;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 120px;
            color: rgba(59, 130, 246, 0.05);
            font-weight: bold;
            z-index: 0;
            text-transform: uppercase;
            letter-spacing: 10px;
        }

        /* Decorative elements */
        .corner-decoration {
            position: absolute;
            width: 60px;
            height: 60px;
            border: 3px solid #3b82f6;
        }

        .top-left {
            top: 5mm;
            left: 5mm;
            border-right: none;
            border-bottom: none;
        }

        .top-right {
            top: 5mm;
            right: 5mm;
            border-left: none;
            border-bottom: none;
        }

        .bottom-left {
            bottom: 5mm;
            left: 5mm;
            border-right: none;
            border-top: none;
        }

        .bottom-right {
            bottom: 5mm;
            right: 5mm;
            border-left: none;
            border-top: none;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border">
            <!-- Corner Decorations -->
            <div class="corner-decoration top-left"></div>
            <div class="corner-decoration top-right"></div>
            <div class="corner-decoration bottom-left"></div>
            <div class="corner-decoration bottom-right"></div>

            <div class="certificate-inner-border">
                <!-- Watermark -->
                <div class="watermark">TheLeap</div>

                <!-- Header -->
                <div class="certificate-header">
                    <div class="organization-name">TheLeap</div>
                    <div class="organization-tagline">Bureau of Technical & Vocational Training Authority</div>
                    <div class="certificate-title">Certificate of Completion</div>
                </div>

                <!-- Content -->
                <div class="certificate-content">
                    <div class="awarded-text">This certificate is proudly awarded to</div>

                    <div class="candidate-name">{{ $candidate->name }}</div>

                    <div class="completion-text">
                        For successfully completing the comprehensive training program in
                        <span class="trade-name">{{ $trade->name }}</span>
                        at {{ $campus->name }}, demonstrating exceptional dedication, skill development,
                        and meeting all the required competency standards.
                    </div>

                    <!-- Details Grid -->
                    <div class="details-grid">
                        <div class="details-row">
                            <div class="details-label">TheLeap ID:</div>
                            <div class="details-value">{{ $candidate->btevta_id }}</div>
                        </div>
                        <div class="details-row">
                            <div class="details-label">CNIC:</div>
                            <div class="details-value">{{ $candidate->formatted_cnic ?? $candidate->cnic }}</div>
                        </div>
                        <div class="details-row">
                            <div class="details-label">Batch:</div>
                            <div class="details-value">{{ $batch->name }} ({{ $batch->batch_code }})</div>
                        </div>
                        <div class="details-row">
                            <div class="details-label">Training Period:</div>
                            <div class="details-value">
                                {{ $batch->start_date ? $batch->start_date->format('d M Y') : 'N/A' }} -
                                {{ $batch->end_date ? $batch->end_date->format('d M Y') : 'N/A' }}
                            </div>
                        </div>
                        <div class="details-row">
                            <div class="details-label">Issue Date:</div>
                            <div class="details-value">{{ $certificate->issue_date ? $certificate->issue_date->format('d M Y') : now()->format('d M Y') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="certificate-footer">
                    <div class="signatures">
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-name">Training Coordinator</div>
                            <div class="signature-title">{{ $campus->name }}</div>
                        </div>
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-name">Campus Director</div>
                            <div class="signature-title">{{ $campus->name }}</div>
                        </div>
                        <div class="signature-block">
                            <div class="signature-line"></div>
                            <div class="signature-name">Project Director</div>
                            <div class="signature-title">TheLeap Authority</div>
                        </div>
                    </div>

                    <div class="certificate-number">
                        Certificate No: {{ $certificate->certificate_number }} |
                        Issued: {{ $certificate->issue_date ? $certificate->issue_date->format('F d, Y') : now()->format('F d, Y') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
