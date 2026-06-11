<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'user_id' => $this->user_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'vehicle_brand' => $this->vehicle_brand,
            'vehicle_model' => $this->vehicle_model,
            'vehicle_plate' => $this->vehicle_plate,
            'vehicle_year' => $this->vehicle_year,
            'scheduled_at' => $this->scheduled_at,
            'complaint' => $this->complaint,
            'admin_notes' => $this->admin_notes,
            'status' => $this->status,
            'service_total' => (float) $this->service_total,
            'parts_total' => (float) $this->parts_total,
            'grand_total' => (float) $this->grand_total,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,
            'paid_at' => $this->paid_at,
            'items' => BookingItemResource::collection($this->whenLoaded('items')),
            'customer' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
