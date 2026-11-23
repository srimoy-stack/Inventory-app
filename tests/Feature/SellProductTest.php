<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deducts_stock_and_returns_the_updated_stock(): void
    {
        $product = $this->product(10);

        $response = $this->postJson("/api/products/{$product->id}/sell", [
            'quantity' => 4,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.stock', 6)
            ->assertJsonPath('data.sku', 'WIDGET-1');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 6,
        ]);
    }

    public function test_it_allows_selling_the_entire_remaining_stock(): void
    {
        $product = $this->product(3);

        $response = $this->postJson("/api/products/{$product->id}/sell", [
            'quantity' => 3,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.stock', 0);
    }

    public function test_it_rejects_a_sale_when_stock_is_insufficient_and_leaves_stock_unchanged(): void
    {
        $product = $this->product(2);

        $response = $this->postJson("/api/products/{$product->id}/sell", [
            'quantity' => 3,
        ]);

        $response->assertStatus(409)
            ->assertExactJson([
                'message' => 'Insufficient stock.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }

    public function test_a_second_sale_cannot_oversell_stock_already_deducted(): void
    {
        $product = $this->product(5);

        $first = $this->postJson("/api/products/{$product->id}/sell", [
            'quantity' => 3,
        ]);
        $second = $this->postJson("/api/products/{$product->id}/sell", [
            'quantity' => 3,
        ]);

        $first->assertOk()->assertJsonPath('data.stock', 2);
        $second->assertStatus(409);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }

    public function test_it_returns_not_found_for_a_missing_product(): void
    {
        $response = $this->postJson('/api/products/999/sell', [
            'quantity' => 1,
        ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Product not found.');
    }

    public function test_it_requires_a_quantity(): void
    {
        $product = $this->product(5);

        $response = $this->postJson("/api/products/{$product->id}/sell", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
    }

    public function test_it_rejects_a_quantity_below_one(): void
    {
        $product = $this->product(5);

        $zero = $this->postJson("/api/products/{$product->id}/sell", [
            'quantity' => 0,
        ]);
        $negative = $this->postJson("/api/products/{$product->id}/sell", [
            'quantity' => -2,
        ]);

        $zero->assertUnprocessable()->assertJsonValidationErrors(['quantity']);
        $negative->assertUnprocessable()->assertJsonValidationErrors(['quantity']);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
    }

    public function test_it_rejects_a_non_integer_quantity(): void
    {
        $product = $this->product(5);

        $response = $this->postJson("/api/products/{$product->id}/sell", [
            'quantity' => 1.5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
    }

    private function product(int $stock): Product
    {
        return Product::query()->create([
            'name' => 'Widget',
            'sku' => 'WIDGET-1',
            'price' => 9.5,
            'stock' => $stock,
        ]);
    }
}
