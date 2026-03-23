<?php

namespace Database\Factories;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Node> */
class NodeFactory extends Factory
{
    protected $model = Node::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => NodeType::Page,
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(3),
            'status' => NodeStatus::Draft,
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => NodeStatus::Published]);
    }

    public function page(): static
    {
        return $this->state(fn () => ['type' => NodeType::Page]);
    }

    public function menu(): static
    {
        return $this->state(fn () => ['type' => NodeType::Menu, 'slug' => null]);
    }
}
