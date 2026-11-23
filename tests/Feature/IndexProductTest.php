<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_products_with_pagination_metadata(): void
    {
        $this->createProducts(25);

        $response = $this->getJson('/api/products?page=2&limit=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'sku', 'price', 'stock', 'created_at'],
                ],
                'meta' => ['current_page', 'per_page', 'last_page', 'total'],
            ]);

        $skus = collect($response->json('data'))->pluck('sku')->all();

        $this->assertSame([
            'SKU-011',
            'SKU-012',
            'SKU-013',
            'SKU-014',
            'SKU-015',
            'SKU-016',
            'SKU-017',
            'SKU-018',
            'SKU-019',
            'SKU-020',
        ], $skus);
    }

    public function test_it_defaults_to_the_first_page_with_fifteen_per_page(): void
    {
        $this->createProducts(20);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_it_returns_an_empty_page_when_no_products_exist(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 0);
    }

    public function test_it_returns_an_empty_data_set_past_the_last_page(): void
    {
        $this->createProducts(3);

        $response = $this->getJson('/api/products?page=4&limit=2');

        $response->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.current_page', 4)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_it_rejects_a_page_below_one(): void
    {
        $response = $this->getJson('/api/products?page=0&limit=10');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_it_rejects_a_non_integer_page(): void
    {
        $response = $this->getJson('/api/products?page=1.5');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    }

    public function test_it_rejects_a_limit_below_one(): void
    {
        $response = $this->getJson('/api/products?limit=0');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_it_rejects_a_non_integer_limit(): void
    {
        $response = $this->getJson('/api/products?limit=ten');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_it_rejects_a_limit_above_one_hundred(): void
    {
        $response = $this->getJson('/api/products?limit=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

    private function createProducts(int $count): void
    {
        foreach (range(1, $count) as $index) {
            Product::query()->create([
                'name' => "Product {$index}",
                'sku' => sprintf('SKU-%03d', $index),
                'price' => 10,
                'stock' => $index,
            ]);
        }
    }
}
