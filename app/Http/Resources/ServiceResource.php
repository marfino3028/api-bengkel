<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'duration_minutes' => $this->duration_minutes,
            'image' => Media::url($this->image),
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
        ];
    }
}
