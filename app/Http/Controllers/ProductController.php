<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListProductsRequest;
use App\Http\Requests\SellProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(ListProductsRequest $request): AnonymousResourceCollection
    {
        $products = $this->products->paginate(
            perPage: $request->integer('limit', 15),
            page: $request->integer('page', 1),
        );

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function sell(SellProductRequest $request, Product $product): ProductResource
    {
        $updated = $this->products->sell($product, $request->integer('quantity'));

        return new ProductResource($updated);
    }
}
