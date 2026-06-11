<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'qty' => $this->qty,
            'subtotal' => (float) $this->subtotal,
        ];
    }
}
