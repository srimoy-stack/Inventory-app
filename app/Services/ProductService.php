<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    public function paginate(int $perPage, int $page): LengthAwarePaginator
    {
        return Product::query()
            ->orderBy('id')
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Subtract stock in one conditional update so overlapping sales cannot
     * both pass the availability check.
     */
    public function sell(Product $product, int $quantity): Product
    {
        $updated = DB::transaction(function () use ($product, $quantity): ?Product {
            $affected = Product::query()
                ->whereKey($product->getKey())
                ->where('stock', '>=', $quantity)
                ->decrement('stock', $quantity);

            if ($affected < 1) {
                return null;
            }

            return $product->refresh();
        });

        if (! $updated instanceof Product) {
            throw new InsufficientStockException;
        }

        return $updated;
    }
}
