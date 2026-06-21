<?php

namespace Modules\Notification\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Models\NotificationLog;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'type'          => fake()->randomElement(['manual_broadcast', 'payment_receipt', 'payment_reminder', 'system_alert']),
            'target_phone'  => '08'.fake()->numerify('#########'),
            'message_body'  => fake()->paragraph(),
            'status'        => fake()->randomElement(['sent', 'failed', 'pending']),
            'error_response' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => ['status' => 'sent', 'error_response' => null]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status'         => 'failed',
            'error_response' => 'Provider returned error',
        ]);
    }
}
