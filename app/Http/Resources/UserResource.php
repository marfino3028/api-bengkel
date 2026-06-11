<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'avatar' => Media::url($this->avatar),
            'bookings_count' => $this->whenCounted('bookings'),
            'orders_count' => $this->whenCounted('orders'),
            'created_at' => $this->created_at,
        ];
    }
}
