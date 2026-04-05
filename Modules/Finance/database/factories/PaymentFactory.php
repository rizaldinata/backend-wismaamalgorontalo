<?php

namespace Modules\Finance\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\Payment;
use Modules\Finance\Enums\PaymentStatus;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => \Modules\Finance\Models\Invoice::factory(),
            'payment_method' => 'manual',
            'payment_proof_path' => 'payments/manual/fake_proof.jpg',
            'status' => PaymentStatus::PENDING,
            'admin_notes' => null,
        ];
    }
}
