<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'price' => (float) $this->price,
            'qty' => $this->qty,
            'subtotal' => (float) $this->subtotal,
            'image' => $this->whenLoaded('product', fn () => Media::url($this->product?->image)),
        ];
    }
}
