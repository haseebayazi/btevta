<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Bank Account',
                'code' => 'bank',
                'icon' => 'bank',
                'requires_account_number' => true,
                'requires_bank_name' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'EasyPaisa',
                'code' => 'easypaisa',
                'icon' => 'mobile',
                'requires_account_number' => true,
                'requires_bank_name' => false,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'JazzCash',
                'code' => 'jazzcash',
                'icon' => 'mobile',
                'requires_account_number' => true,
                'requires_bank_name' => false,
                'is_active' => true,
                'display_order' => 3,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }

        $this->command->info('Payment methods seeded successfully.');
    }
}
