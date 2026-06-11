<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->where('role', 'customer')
            ->withCount(['bookings', 'orders']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(15)->withQueryString();

        return UserResource::collection($customers);
    }

    public function show(User $customer)
    {
        abort_unless($customer->role === 'customer', 404);

        return response()->json([
            'data' => [
                'customer' => new UserResource($customer),
                'bookings' => BookingResource::collection($customer->bookings()->with('items')->latest()->get()),
                'orders' => OrderResource::collection($customer->orders()->with('items')->latest()->get()),
            ],
        ]);
    }
}
