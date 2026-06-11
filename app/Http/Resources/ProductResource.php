<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'slug' => $this->slug,
            'brand' => $this->brand,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'image' => Media::url($this->image),
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at,
        ];
    }
}
