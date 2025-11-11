<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies supported for remittance tracking.
    | Format: [currency_code => currency_name]
    |
    */

    'currencies' => [
        'PKR' => 'Pakistani Rupee',
        'USD' => 'US Dollar',
        'SAR' => 'Saudi Riyal',
        'AED' => 'UAE Dirham',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'QAR' => 'Qatari Riyal',
        'KWD' => 'Kuwaiti Dinar',
        'OMR' => 'Omani Rial',
        'BHD' => 'Bahraini Dinar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Remittance Purpose Categories
    |--------------------------------------------------------------------------
    |
    | Predefined categories for remittance usage purposes.
    |
    */

    'purposes' => [
        'education' => 'Education',
        'health' => 'Health & Medical',
        'rent' => 'Rent & Housing',
        'food' => 'Food & Groceries',
        'savings' => 'Savings & Investments',
        'debt_repayment' => 'Debt Repayment',
        'family_support' => 'Family Support',
        'business_investment' => 'Business Investment',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transfer Methods
    |--------------------------------------------------------------------------
    |
    | Common transfer methods for remittances.
    |
    */

    'transfer_methods' => [
        'bank_transfer' => 'Bank Transfer',
        'money_exchange' => 'Money Exchange (Western Union, MoneyGram, etc.)',
        'mobile_wallet' => 'Mobile Wallet (JazzCash, Easypaisa, etc.)',
        'cash_delivery' => 'Cash Delivery',
        'online_payment' => 'Online Payment Service',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Beneficiary Relationships
    |--------------------------------------------------------------------------
    |
    | Possible relationships between worker and beneficiary.
    |
    */

    'relationships' => [
        'spouse' => 'Spouse',
        'father' => 'Father',
        'mother' => 'Mother',
        'son' => 'Son',
        'daughter' => 'Daughter',
        'brother' => 'Brother',
        'sister' => 'Sister',
        'other_relative' => 'Other Relative',
        'self' => 'Self',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for receipt file uploads.
    |
    */

    'max_file_size' => 5120, // KB (5 MB)
    'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png'],
    'storage_path' => 'remittances/receipts',

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for generating automated alerts.
    |
    */

    'alert_threshold_days' => 90, // Alert if no remittance in X days
    'large_amount_threshold' => 500000, // PKR - Alert for unusually large amounts
    'proof_required_days' => 7, // Days after remittance to require proof upload

    /*
    |--------------------------------------------------------------------------
    | Report Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for remittance reports and analytics.
    |
    */

    'pagination_limit' => 20,
    'export_limit' => 10000, // Maximum records per export
    'chart_data_months' => 12, // Months of data to show in charts

    /*
    |--------------------------------------------------------------------------
    | Status Labels
    |--------------------------------------------------------------------------
    |
    | Display labels for remittance statuses.
    |
    */

    'statuses' => [
        'pending' => ['label' => 'Pending Verification', 'class' => 'bg-yellow-100 text-yellow-800'],
        'verified' => ['label' => 'Verified', 'class' => 'bg-green-100 text-green-800'],
        'flagged' => ['label' => 'Flagged for Review', 'class' => 'bg-red-100 text-red-800'],
        'completed' => ['label' => 'Completed', 'class' => 'bg-blue-100 text-blue-800'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Types
    |--------------------------------------------------------------------------
    |
    | Types of proof documents for remittances.
    |
    */

    'document_types' => [
        'bank_receipt' => 'Bank Receipt',
        'transfer_slip' => 'Transfer Slip',
        'mobile_screenshot' => 'Mobile App Screenshot',
        'email_confirmation' => 'Email Confirmation',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for generating automated alerts.
    |
    */

    'alert_thresholds' => [
        'missing_remittance_days' => 90, // Alert if no remittance in X days
        'proof_upload_days' => 30, // Alert if proof not uploaded within X days
        'first_remittance_days' => 60, // Alert if first remittance not sent within X days
        'low_frequency_months' => 6, // Check frequency after X months
        'min_expected_remittances' => 3, // Minimum remittances expected in frequency period
    ],

];
