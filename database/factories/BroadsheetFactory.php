<?php

namespace Database\Factories;

use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Broadsheet>
 */
class BroadsheetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $village = Village::factory()->create();

        return [
            'author_id' => User::factory(),
            'title' => fake()->sentence(4),
            'content' => [['type' => 'paragraph', 'children' => [['text' => fake()->paragraph()]]]],
            'plain_text' => fake()->paragraph(),
            'location_type' => 'village',
            'location_id' => $village->id,
            'barony_id' => $village->barony_id,
            'kingdom_id' => $village->barony->kingdom_id,
            'location_name' => $village->name,
            'published_at' => now(),
        ];
    }

    /**
     * Set the broadsheet to be at a specific village.
     */
    public function atVillage(Village $village): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => 'village',
            'location_id' => $village->id,
            'barony_id' => $village->barony_id,
            'kingdom_id' => $village->barony->kingdom_id,
            'location_name' => $village->name,
        ]);
    }

    /**
     * Set the broadsheet to be at a specific town.
     */
    public function atTown(Town $town): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => 'town',
            'location_id' => $town->id,
            'barony_id' => $town->barony_id,
            'kingdom_id' => $town->barony->kingdom_id,
            'location_name' => $town->name,
        ]);
    }
}
