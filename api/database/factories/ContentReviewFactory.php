<?php

namespace Database\Factories;

use App\Models\ContentReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentReview>
 */
class ContentReviewFactory extends Factory
{
    protected $model = ContentReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'target_type' => ContentReview::TARGET_CROP,
            'target_slug' => fake()->unique()->slug(2),
            'status' => ContentReview::STATUS_DRAFT,
            'content_payload' => null,
            'reviewer_notes' => null,
            'submitted_by' => User::factory(),
            'reviewer_id' => null,
            'submitted_at' => null,
            'decided_at' => null,
        ];
    }

    public function submitted(): self
    {
        return $this->state(fn () => [
            'status' => ContentReview::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'status' => ContentReview::STATUS_APPROVED,
            'reviewer_id' => User::factory()->superuser(),
            'submitted_at' => now()->subHour(),
            'decided_at' => now(),
        ]);
    }

    public function changesRequested(): self
    {
        return $this->state(fn () => [
            'status' => ContentReview::STATUS_CHANGES_REQUESTED,
            'reviewer_id' => User::factory()->superuser(),
            'reviewer_notes' => 'Needs more variety detail and a Swahili pass.',
            'submitted_at' => now()->subHour(),
            'decided_at' => now(),
        ]);
    }

    public function forDisease(): self
    {
        return $this->state(fn () => [
            'target_type' => ContentReview::TARGET_DISEASE,
        ]);
    }
}
