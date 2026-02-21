<?php

namespace Database\Factories;

use App\Models\Broadsheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BroadsheetComment>
 */
class BroadsheetCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'broadsheet_id' => Broadsheet::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->sentence(),
        ];
    }

    /**
     * Make this comment a reply to a parent comment.
     */
    public function replyTo(\App\Models\BroadsheetComment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'broadsheet_id' => $parent->broadsheet_id,
            'parent_id' => $parent->id,
        ]);
    }
}
