<?php

namespace Database\Factories;

use App\Models\Remittance;
use App\Models\RemittanceReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class RemittanceReceiptFactory extends Factory
{
    protected $model = RemittanceReceipt::class;

    public function definition(): array
    {
        return [
            'remittance_id' => Remittance::factory(),
            'receipt_number' => $this->faker->unique()->bothify('REC-####-????'),
            'file_path' => 'receipts/' . $this->faker->uuid() . '.pdf',
            'upload_date' => $this->faker->date(),
        ];
    }
}
