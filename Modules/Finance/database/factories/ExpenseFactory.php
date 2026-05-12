<?php

namespace Modules\Finance\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\Expense;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'amount' => fake()->randomFloat(2, 10000, 1000000),
            'expense_date' => now()->toDateString(),
            'reference_id' => null,
            'reference_type' => null,
        ];
    }
}
