<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_product_and_returns_it_as_json(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Warehouse Shelf',
            'sku' => 'SHELF-001',
            'price' => 49.5,
            'stock' => 12,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Warehouse Shelf')
            ->assertJsonPath('data.sku', 'SHELF-001')
            ->assertJsonPath('data.price', '49.50')
            ->assertJsonPath('data.stock', 12)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'sku', 'price', 'stock', 'created_at'],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Warehouse Shelf',
            'sku' => 'SHELF-001',
            'stock' => 12,
        ]);
    }

    public function test_it_accepts_zero_price_and_zero_stock(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Sample',
            'sku' => 'SAMPLE-0',
            'price' => 0,
            'stock' => 0,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.price', '0.00')
            ->assertJsonPath('data.stock', 0);
    }

    public function test_it_requires_a_name(): void
    {
        $response = $this->postJson('/api/products', $this->payload(['name' => '']));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_it_requires_price_and_stock(): void
    {
        $payload = $this->payload();
        unset($payload['price'], $payload['stock']);

        $response = $this->postJson('/api/products', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'stock']);
    }

    public function test_it_requires_a_unique_sku(): void
    {
        Product::query()->create([
            'name' => 'Existing',
            'sku' => 'DUP-1',
            'price' => 10,
            'stock' => 1,
        ]);

        $missing = $this->postJson('/api/products', $this->payload(['sku' => '']));
        $duplicate = $this->postJson('/api/products', $this->payload(['sku' => 'DUP-1']));

        $missing->assertUnprocessable()->assertJsonValidationErrors(['sku']);
        $duplicate->assertUnprocessable()->assertJsonValidationErrors(['sku']);
        $this->assertSame(1, Product::query()->where('sku', 'DUP-1')->count());
    }

    public function test_it_rejects_a_negative_price(): void
    {
        $response = $this->postJson('/api/products', $this->payload(['price' => -0.01]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_it_rejects_a_non_numeric_price(): void
    {
        $response = $this->postJson('/api/products', $this->payload(['price' => 'free']));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_it_rejects_a_negative_stock(): void
    {
        $response = $this->postJson('/api/products', $this->payload(['stock' => -1]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock']);
    }

    public function test_it_rejects_a_non_integer_stock(): void
    {
        $response = $this->postJson('/api/products', $this->payload(['stock' => 1.5]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cable',
            'sku' => 'CABLE-1',
            'price' => 5,
            'stock' => 3,
        ], $overrides);
    }
}
