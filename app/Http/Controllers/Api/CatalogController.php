<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ServiceResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function categories()
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function products(Request $request)
    {
        $query = Product::query()->with('category')->where('is_active', true);

        if ($slug = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        $perPage = min((int) $request->query('per_page', 12), 50);

        return ProductResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function product(string $slug)
    {
        $product = Product::query()->with('category')
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductResource($product);
    }

    public function services(Request $request)
    {
        $query = Service::query()->where('is_active', true);

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        return ServiceResource::collection($query->orderBy('name')->get());
    }

    public function service(string $slug)
    {
        $service = Service::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return new ServiceResource($service);
    }

    public function banners()
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return BannerResource::collection($banners);
    }

    public function settings()
    {
        return response()->json(['data' => Setting::allAsMap()]);
    }
}
