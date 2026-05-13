<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\DiseaseDetection;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiseaseDetection>
 */
class DiseaseDetectionFactory extends Factory
{
    protected $model = DiseaseDetection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'season_id' => null,
            'crop_id' => Crop::factory(),
            'image_url' => 'https://example.com/disease/'.fake()->uuid().'.jpg',
            'provider' => DiseaseDetection::PROVIDER_MOCK,
            'top_diagnosis' => 'Early Blight',
            'confidence' => fake()->randomFloat(4, 0.6, 0.99),
            'engine_response' => ['suggestions' => [['name' => 'Early Blight', 'probability' => 0.91]]],
            'treatments' => [['generic' => 'Mancozeb', 'pcpb' => 'Ridomil Gold MZ 68 WG']],
            'opt_in_for_training' => false,
            'captured_at' => now(),
        ];
    }

    public function fromCropHealth(): self
    {
        return $this->state(fn () => ['provider' => DiseaseDetection::PROVIDER_CROP_HEALTH]);
    }

    public function lowConfidence(): self
    {
        return $this->state(fn () => ['confidence' => 0.3, 'top_diagnosis' => 'Inconclusive']);
    }
}
