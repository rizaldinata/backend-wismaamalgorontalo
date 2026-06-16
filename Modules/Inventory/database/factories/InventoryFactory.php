<?php

namespace Modules\Inventory\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Enums\ItemCondition;
use Modules\Inventory\Models\Inventory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'condition' => ItemCondition::GOOD,
            'purchase_price' => null,
        ];
    }

    public function withPurchasePrice(float $price = 500000): static
    {
        return $this->state(['purchase_price' => $price]);
    }
}
