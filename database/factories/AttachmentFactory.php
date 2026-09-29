<?php

namespace Database\Factories;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->unique()->word().'.png';

        return [
            'filename' => $filename,
            'path' => 'attachments/'.$filename,
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1_000, 5_000_000),
        ];
    }
}
