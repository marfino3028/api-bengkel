<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/health', fn () => response()->json(['status' => 'ok', 'time' => now()]));

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/categories', [CatalogController::class, 'categories']);
Route::get('/products', [CatalogController::class, 'products']);
Route::get('/products/{slug}', [CatalogController::class, 'product']);
Route::get('/services', [CatalogController::class, 'services']);
Route::get('/services/{slug}', [CatalogController::class, 'service']);
Route::get('/banners', [CatalogController::class, 'banners']);
Route::get('/settings', [CatalogController::class, 'settings']);

// Webhook Midtrans (dipanggil server Midtrans, diverifikasi via signature)
Route::post('/payments/notification', [PaymentController::class, 'notification']);

/*
|--------------------------------------------------------------------------
| Authenticated (customer / admin)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Booking servis
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{code}', [BookingController::class, 'show']);
    Route::post('/bookings/{code}/cancel', [BookingController::class, 'cancel']);

    // Order sparepart
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{code}', [OrderController::class, 'show']);
    Route::post('/orders/{code}/cancel', [OrderController::class, 'cancel']);

    // Pembayaran online (Midtrans Snap)
    Route::post('/orders/{code}/pay', [PaymentController::class, 'payOrder']);
    Route::post('/bookings/{code}/pay', [PaymentController::class, 'payBooking']);
});

/*
|--------------------------------------------------------------------------
| Admin (role: admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index']);

    Route::apiResource('categories', Admin\CategoryController::class);
    Route::apiResource('products', Admin\ProductController::class);
    Route::apiResource('services', Admin\ServiceController::class);
    Route::apiResource('banners', Admin\BannerController::class);

    Route::post('/upload', [Admin\UploadController::class, 'store']);

    // Bookings
    Route::get('/bookings', [Admin\BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [Admin\BookingController::class, 'show']);
    Route::put('/bookings/{booking}/status', [Admin\BookingController::class, 'updateStatus']);
    Route::post('/bookings/{booking}/items', [Admin\BookingController::class, 'addItem']);
    Route::delete('/bookings/{booking}/items/{item}', [Admin\BookingController::class, 'removeItem']);
    Route::put('/bookings/{booking}/payment', [Admin\BookingController::class, 'updatePayment']);

    // Orders
    Route::get('/orders', [Admin\OrderController::class, 'index']);
    Route::get('/orders/{order}', [Admin\OrderController::class, 'show']);
    Route::put('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus']);
    Route::put('/orders/{order}/payment', [Admin\OrderController::class, 'updatePayment']);

    // Customers
    Route::get('/customers', [Admin\CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [Admin\CustomerController::class, 'show']);

    // Settings
    Route::get('/settings', [Admin\SettingController::class, 'index']);
    Route::put('/settings', [Admin\SettingController::class, 'update']);
});
