<?php

namespace Modules\Finance\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Enums\JenisPengeluaranTetap;
use Modules\Finance\Models\FixedExpenseEntry;

class FixedExpenseEntryFactory extends Factory
{
    protected $model = FixedExpenseEntry::class;

    public function definition(): array
    {
        return [
            'jenis' => fake()->randomElement(JenisPengeluaranTetap::cases())->value,
            'bulan' => fake()->numberBetween(1, 12),
            'tahun' => (int) now()->format('Y'),
            'amount' => fake()->randomFloat(2, 50000, 2000000),
            'is_filled' => true,
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => null,
        ];
    }

    public function kosong(): static
    {
        return $this->state(['amount' => 0, 'is_filled' => false]);
    }

    public function listrik(): static
    {
        return $this->state(['jenis' => JenisPengeluaranTetap::LISTRIK->value]);
    }

    public function air(): static
    {
        return $this->state(['jenis' => JenisPengeluaranTetap::AIR->value]);
    }

    public function wifi(): static
    {
        return $this->state(['jenis' => JenisPengeluaranTetap::WIFI->value]);
    }

    public function untukBulan(int $bulan, int $tahun): static
    {
        return $this->state(['bulan' => $bulan, 'tahun' => $tahun]);
    }
}
