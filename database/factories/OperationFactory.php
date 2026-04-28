<?php

namespace Database\Factories;

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Operation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Operation>
 */
class OperationFactory extends Factory
{
    protected $model = Operation::class;

    public function definition(): array
    {
        return [
            'building_id' => null,
            'typology' => PrescriptionTypologyEnum::PROTRUSOR->value,
            'status' => OperationStatusEnum::DRAFT->value,
            'batch_number' => fake()->unique()->bothify('BATCH-####'),
        ];
    }
}
