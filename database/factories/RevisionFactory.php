<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Revision>
 */
class RevisionFactory extends Factory
{
    protected $model = Revision::class;

    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'opened_at' => now(),
            'opened_by' => User::factory(),
            'last_submitted_at' => null,
            'closed_at' => null,
            'closed_by' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn() => [
            'closed_at' => null,
            'closed_by' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn() => [
            'closed_at' => now(),
            'closed_by' => User::factory(),
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn() => ['last_submitted_at' => now()]);
    }
}
