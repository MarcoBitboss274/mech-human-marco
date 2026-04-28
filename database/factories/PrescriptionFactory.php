<?php

namespace Database\Factories;

use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Operation;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'operation_id' => Operation::factory(),
            'building_id' => null,
            'user_id' => User::factory(),
            'status' => PrescriptionStatusEnum::DRAFT->value,
            'typology' => PrescriptionTypologyEnum::PROTRUSOR->value,
            'ref' => fake()->bothify('REF-####'),
            'name' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'age' => fake()->numberBetween(18, 90),
            'gender' => fake()->randomElement(['M', 'F']),
            'manual' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn() => ['status' => PrescriptionStatusEnum::DRAFT->value]);
    }

    public function sent(): static
    {
        return $this->state(fn() => [
            'status' => PrescriptionStatusEnum::SENT->value,
            'send_at' => now(),
            'expire_at' => now()->addMonths(6),
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn() => [
            'status' => PrescriptionStatusEnum::CONFIRMED->value,
            'send_at' => now()->subDay(),
            'expire_at' => now()->subDay()->addMonths(6),
            'confirmed_at' => now(),
        ]);
    }
}
