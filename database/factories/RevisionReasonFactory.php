<?php

namespace Database\Factories;

use App\Models\Revision;
use App\Models\RevisionReason;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevisionReason>
 */
class RevisionReasonFactory extends Factory
{
    protected $model = RevisionReason::class;

    public function definition(): array
    {
        return [
            'revision_id' => Revision::factory(),
            'content' => fake()->sentence(8),
            'created_by' => User::factory(),
        ];
    }
}
