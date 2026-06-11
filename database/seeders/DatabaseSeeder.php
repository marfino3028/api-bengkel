<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private function img(string $text, string $bg = '1D4ED8', int $w = 600, int $h = 600): string
    {
        return "https://placehold.co/{$w}x{$h}/{$bg}/FFFFFF/png?text=".urlencode($text);
    }

    public function run(): void
    {
        /* ---------------- Settings ---------------- */
        $settings = [
            'site_name' => 'Bengkel Motor Juara',
            'tagline' => 'Servis Cepat, Sparepart Lengkap, Harga Bersahabat',
            'about' => 'Bengkel Motor Juara melayani servis berkala, perbaikan, dan penjualan sparepart motor berkualitas untuk semua merek. Ditangani mekanik berpengalaman dengan peralatan modern.',
            'address' => 'Jl. Raya Otomotif No. 88, Jakarta Selatan',
            'phone' => '021-1234567',
            'whatsapp' => '6281234567890',
            'email' => 'halo@bengkeljuara.com',
            'hours' => 'Senin - Sabtu: 08.00 - 17.00 WIB',
            'instagram' => 'bengkeljuara',
            'facebook' => 'bengkeljuara',
            'maps_url' => 'https://maps.google.com',
            'logo' => $this->img('BMJ', '2563EB', 200, 200),
        ];
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        /* ---------------- Users ---------------- */
        $admin = User::updateOrCreate(
            ['email' => 'admin@bengkelku.com'],
            [
                'name' => 'Admin Bengkel',
                'phone' => '6281200000001',
                'role' => 'admin',
                'password' => 'password',
            ]
        );

        $customer = User::updateOrCreate(
            ['email' => 'budi@mail.com'],
            [
                'name' => 'Budi Santoso',
                'phone' => '6281211112222',
                'role' => 'customer',
                'password' => 'password',
            ]
        );

        /* ---------------- Categories ---------------- */
        $categoriesData = [
            ['Oli & Pelumas', 'droplet'],
            ['Ban', 'circle'],
            ['Kampas Rem', 'disc'],
            ['Aki', 'battery'],
            ['Filter', 'filter'],
            ['Busi', 'zap'],
            ['Lampu', 'lightbulb'],
            ['Rantai & Gir', 'settings'],
            ['Body & Aksesoris', 'shield'],
            ['Kelistrikan', 'plug'],
        ];
        $categories = [];
        foreach ($categoriesData as $i => [$name, $icon]) {
            $categories[$name] = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'is_active' => true, 'sort_order' => $i,
                 'description' => 'Sparepart kategori '.$name.' untuk berbagai merek motor.']
            );
        }

        /* ---------------- Products ---------------- */
        $productsData = [
            ['Oli & Pelumas', 'Oli Mesin Synthetic 10W-40 1L', 'Yamalube', 55000, 120, true],
            ['Oli & Pelumas', 'Oli Matic CVT 120ml', 'AHM', 38000, 90, false],
            ['Oli & Pelumas', 'Oli Gardan Matic 100ml', 'Federal', 22000, 75, false],
            ['Ban', 'Ban Tubeless 80/90-14', 'IRC', 185000, 40, true],
            ['Ban', 'Ban Tubeless 90/80-17', 'FDR', 245000, 30, false],
            ['Kampas Rem', 'Kampas Rem Depan Cakram', 'Aspira', 45000, 60, true],
            ['Kampas Rem', 'Kampas Rem Belakang Tromol', 'Indopart', 38000, 55, false],
            ['Aki', 'Aki Kering GTZ5S 12V', 'GS Astra', 165000, 25, true],
            ['Aki', 'Aki Kering GTZ6V 12V', 'Yuasa', 195000, 18, false],
            ['Filter', 'Filter Udara Original', 'AHM', 42000, 70, false],
            ['Filter', 'Filter Oli', 'Federal', 18000, 100, false],
            ['Busi', 'Busi Iridium', 'NGK', 95000, 80, true],
            ['Busi', 'Busi Standar', 'Denso', 25000, 150, false],
            ['Lampu', 'Lampu LED Headlamp H6', 'Osram', 120000, 45, true],
            ['Rantai & Gir', 'Paket Rantai & Gir Set', 'SSS', 215000, 35, true],
            ['Rantai & Gir', 'Rantai Roller 428H', 'DID', 135000, 50, false],
            ['Body & Aksesoris', 'Spion Set Universal', 'KOSO', 85000, 40, false],
            ['Body & Aksesoris', 'Cover Body Pelindung', 'Variasi', 65000, 30, false],
            ['Kelistrikan', 'Kiprok / Regulator', 'Original', 110000, 28, false],
            ['Kelistrikan', 'Relay Klakson', 'Bosch', 35000, 60, false],
        ];
        $bgPalette = ['1D4ED8', '2563EB', '1E3A8A', 'F97316', '0F172A'];
        foreach ($productsData as $i => [$cat, $name, $brand, $price, $stock, $featured]) {
            Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => $categories[$cat]->id,
                    'name' => $name,
                    'brand' => $brand,
                    'sku' => 'SKU-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'description' => $name.' berkualitas, kompatibel dengan berbagai tipe motor. Garansi keaslian produk.',
                    'price' => $price,
                    'stock' => $stock,
                    'image' => $this->img($name, $bgPalette[$i % count($bgPalette)]),
                    'is_active' => true,
                    'is_featured' => $featured,
                ]
            );
        }

        /* ---------------- Services ---------------- */
        $servicesData = [
            ['Servis Ringan', 60, 75000, true, 'Pengecekan & penyetelan ringan: rem, rantai, tekanan ban, dan kelistrikan dasar.'],
            ['Servis Besar (Tune Up)', 120, 175000, true, 'Servis menyeluruh: pembersihan karburator/injeksi, penyetelan klep, ganti oli, dan pengecekan total.'],
            ['Ganti Oli', 20, 25000, true, 'Penggantian oli mesin termasuk jasa (belum termasuk oli).'],
            ['Servis Rem', 45, 50000, false, 'Pengecekan dan penggantian kampas rem, penyetelan sistem pengereman.'],
            ['Servis CVT (Matic)', 90, 95000, true, 'Pembersihan dan penggantian komponen CVT untuk motor matic.'],
            ['Servis Injeksi', 60, 110000, false, 'Diagnosa & pembersihan sistem injeksi dengan alat scanner.'],
            ['Ganti Ban', 30, 30000, false, 'Jasa bongkar pasang dan ganti ban (belum termasuk ban).'],
            ['Cuci Motor & Detailing', 45, 35000, false, 'Cuci menyeluruh dan pemolesan body motor.'],
        ];
        foreach ($servicesData as [$name, $dur, $price, $featured, $desc]) {
            Service::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $desc,
                    'price' => $price,
                    'duration_minutes' => $dur,
                    'image' => $this->img($name, 'F97316', 800, 500),
                    'is_active' => true,
                    'is_featured' => $featured,
                ]
            );
        }

        /* ---------------- Banners ---------------- */
        $bannersData = [
            ['Servis Motor Lebih Mudah', 'Booking online, datang, langsung dikerjakan mekanik ahli', 0],
            ['Promo Ganti Oli', 'Hemat untuk paket servis ringan + ganti oli bulan ini', 1],
            ['Sparepart Original & Bergaransi', 'Ratusan pilihan sparepart untuk semua merek motor', 2],
        ];
        foreach ($bannersData as [$title, $sub, $order]) {
            Banner::updateOrCreate(
                ['title' => $title],
                [
                    'subtitle' => $sub,
                    'image' => $this->img($title, $bgPalette[$order % count($bgPalette)], 1200, 500),
                    'link' => '/katalog',
                    'is_active' => true,
                    'sort_order' => $order,
                ]
            );
        }

        /* ---------------- Sample transactions ---------------- */
        $this->seedSampleBooking($customer);
        $this->seedSampleOrder($customer);
    }

    private function seedSampleBooking(User $customer): void
    {
        if (Booking::where('user_id', $customer->id)->exists()) {
            return;
        }

        $service = Service::where('slug', 'servis-ringan')->first();

        $booking = Booking::create([
            'booking_code' => 'BK-'.Carbon::now()->format('Ymd').'-0001',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'vehicle_brand' => 'Honda',
            'vehicle_model' => 'Beat',
            'vehicle_plate' => 'B 1234 ABC',
            'vehicle_year' => '2021',
            'scheduled_at' => Carbon::now()->addDays(2)->setTime(10, 0),
            'complaint' => 'Motor terasa kurang bertenaga dan suara mesin kasar.',
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);

        if ($service) {
            $booking->items()->create([
                'item_type' => 'service',
                'item_id' => $service->id,
                'name' => $service->name,
                'price' => $service->price,
                'qty' => 1,
                'subtotal' => $service->price,
            ]);
            $booking->recalculateTotals();
        }
    }

    private function seedSampleOrder(User $customer): void
    {
        if (Order::where('user_id', $customer->id)->exists()) {
            return;
        }

        $product = Product::where('slug', 'oli-mesin-synthetic-10w-40-1l')->first();
        if (! $product) {
            return;
        }

        $order = Order::create([
            'order_code' => 'ORD-'.Carbon::now()->format('Ymd').'-0001',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'fulfillment' => 'pickup',
            'subtotal' => 0,
            'shipping_cost' => 0,
            'total' => 0,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'paid_at' => Carbon::now()->subDay(),
        ]);

        $qty = 2;
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'qty' => $qty,
            'subtotal' => $product->price * $qty,
        ]);

        $subtotal = $product->price * $qty;
        $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);
    }
}
