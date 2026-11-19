<?php

namespace Database\Seeders;

use App\Enums\WorkOrderStatus;
use App\Models\{Booking, Customer, Mechanic, Service, Sparepart, User, Vehicle};
use App\Services\{BookingService, WorkOrderService};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        config(['mail.default' => 'log', 'queue.default' => 'sync']);
        Mail::fake();

        $admin = User::create(['name' => 'Admin Bengkel', 'email' => 'admin@bengkel.test', 'password' => 'password', 'role' => 'admin']);
        $kasir1 = User::create(['name' => 'Kasir Satu', 'email' => 'kasir1@bengkel.test', 'password' => 'password', 'role' => 'kasir']);
        User::create(['name' => 'Kasir Dua', 'email' => 'kasir2@bengkel.test', 'password' => 'password', 'role' => 'kasir']);

        $mechanics = Mechanic::factory()->count(4)->create()->push(Mechanic::factory()->create(['name' => 'Mekanik Nonaktif', 'is_active' => false]));
        $services = collect([
            ['name' => 'Ganti Oli', 'price' => 75000, 'est_minutes' => 30], ['name' => 'Servis Ringan', 'price' => 150000, 'est_minutes' => 60],
            ['name' => 'Tune Up', 'price' => 250000, 'est_minutes' => 120], ['name' => 'Ganti Kampas Rem', 'price' => 180000, 'est_minutes' => 60],
            ['name' => 'Servis CVT', 'price' => 220000, 'est_minutes' => 90], ['name' => 'Balancing', 'price' => 200000, 'est_minutes' => 60],
            ['name' => 'Spooring', 'price' => 350000, 'est_minutes' => 120], ['name' => 'Ganti Aki', 'price' => 850000, 'est_minutes' => 45],
            ['name' => 'Tambal Ban', 'price' => 30000, 'est_minutes' => 30], ['name' => 'Cuci Mesin', 'price' => 100000, 'est_minutes' => 45],
        ])->map(fn ($data) => Service::create($data));
        $parts = Sparepart::factory()->count(13)->create();
        $parts->push(Sparepart::factory()->create(['sku' => 'LOW-001', 'stock' => 2, 'min_stock' => 5]));
        $parts->push(Sparepart::factory()->create(['sku' => 'LOW-002', 'stock' => 1, 'min_stock' => 5]));

        $customers = Customer::factory()->count(10)->create();
        $vehicles = collect();
        foreach ($customers as $i => $customer) {
            $vehicles->push(Vehicle::factory()->create(['customer_id' => $customer->id, 'plate_number' => 'B'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT).'ABC']));
            if ($i < 2) $vehicles->push(Vehicle::factory()->create(['customer_id' => $customer->id, 'plate_number' => 'D'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT).'XYZ']));
        }

        $orders = collect();
        $woService = app(WorkOrderService::class);
        foreach ($vehicles->take(8) as $vehicle) $orders->push($woService->register($vehicle, 'Pemeriksaan berkala', $kasir1));
        $woService->start($orders[0], $mechanics[0]);
        $woService->start($orders[1], $mechanics[0]);
        $woService->start($orders[2], $mechanics[1]);
        $woService->addService($orders[2], $services[0]);
        $woService->finish($orders[2], 0);
        $woService->pay($orders[2], 'tunai', $orders[2]->fresh()->total);
        $woService->start($orders[3], $mechanics[2]);
        $woService->addService($orders[3], $services[1]);
        $woService->finish($orders[3], 10000);
        $woService->pay($orders[3], 'qris', $orders[3]->fresh()->total);
        $orders[3]->update(['queue_date' => today()->subDays(2)]);
        $woService->cancel($orders[4]);
        $woService->start($orders[5], $mechanics[3]);
        $woService->addService($orders[5], $services[2]);
        $woService->finish($orders[5], 0);
        $woService->pay($orders[5], 'transfer', $orders[5]->fresh()->total);
        $orders[6]->update(['queue_date' => today()->subDay()]);
        $woService->start($orders[7], $mechanics[3]);
        $orders[7]->update(['tracking_code' => 'TRACK234']);

        $bookingService = app(BookingService::class);
        $bookingDate = today()->addDay()->toDateString();
        foreach (range(1, 6) as $i) {
            $booking = $bookingService->create([
                'customer_name' => 'Booking Demo '.$i, 'phone' => '0812345678'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'email' => $i <= 3 ? 'booking'.$i.'@bengkel.test' : null, 'plate_number' => 'F'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'BOOK',
                'vehicle_type' => 'motor', 'brand' => 'Honda', 'model' => 'Vario', 'service_id' => $services[0]->id,
                'complaint' => 'Booking demo', 'booking_date' => $bookingDate, 'slot_time' => $i <= 3 ? '09:00' : '10:00',
            ]);
            if ($i === 1) $booking->update(['booking_code' => 'BKNG2345']);
        }
    }
}
