<?php

namespace Database\Factories;

use App\Models\DiseaseDetection;
use App\Models\DiseaseFeedback;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiseaseFeedback>
 */
class DiseaseFeedbackFactory extends Factory
{
    protected $model = DiseaseFeedback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'disease_detection_id' => DiseaseDetection::factory(),
            'user_id' => User::factory(),
            'verdict' => DiseaseFeedback::VERDICT_CORRECT,
            'correction_key' => null,
            'correction_text' => null,
        ];
    }

    public function incorrect(): self
    {
        return $this->state(fn () => [
            'verdict' => DiseaseFeedback::VERDICT_INCORRECT,
            'correction_text' => 'It was actually spider mites.',
        ]);
    }
}
