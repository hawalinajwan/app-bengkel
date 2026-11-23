<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Service;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\BookingService;
use App\Services\WorkOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    private User $kasir;

    private string $dpProof = 'dp-proofs/demo-transfer.svg';

    public function run(): void
    {
        config(['mail.default' => 'log', 'queue.default' => 'sync']);
        Mail::fake();

        $this->seedDpProof();

        $mechanics = $this->seedMechanics();
        $services = $this->seedServices();
        $parts = $this->seedSpareparts();
        $customers = $this->seedCustomers();
        $vehicles = $this->seedVehicles($customers);

        $this->seedWorkOrders($vehicles, $mechanics, $services, $parts);
        $this->seedBookings($services);
    }

    private function seedDpProof(): void
    {
        Storage::disk('local')->put($this->dpProof, <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="720" height="480" viewBox="0 0 720 480">
  <rect width="720" height="480" fill="#f7f3ea"/>
  <rect x="24" y="24" width="672" height="432" fill="#ffffff" stroke="#d9d2c3" stroke-width="2"/>
  <text x="48" y="88" font-family="monospace" font-size="22" fill="#173c32">BUKTI TRANSFER DP</text>
  <text x="48" y="140" font-family="monospace" font-size="16" fill="#5b6660">Bank Mandiri · 13700xxxxxxx</text>
  <text x="48" y="172" font-family="monospace" font-size="16" fill="#5b6660">Penerima: Bengkel Anda</text>
  <text x="48" y="228" font-family="monospace" font-size="34" fill="#173c32">Rp 50.000</text>
  <text x="48" y="272" font-family="monospace" font-size="16" fill="#5b6660">Berita: DP booking</text>
  <text x="48" y="404" font-family="monospace" font-size="14" fill="#8a938d">Contoh bukti untuk data demo</text>
</svg>
SVG);
    }

    private function seedMechanics(): Collection
    {
        User::create([
            'name' => 'Admin Bengkel',
            'email' => 'admin@bengkel.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->kasir = User::create([
            'name' => 'Kasir Satu',
            'email' => 'kasir1@bengkel.test',
            'password' => 'password',
            'role' => 'kasir',
        ]);

        User::create([
            'name' => 'Kasir Dua',
            'email' => 'kasir2@bengkel.test',
            'password' => 'password',
            'role' => 'kasir',
        ]);

        return collect([
            ['name' => 'Budi Santoso', 'phone' => '081200000001', 'is_active' => true],
            ['name' => 'Agus Pratama', 'phone' => '081200000002', 'is_active' => true],
            ['name' => 'Rudi Hartono', 'phone' => '081200000003', 'is_active' => true],
            ['name' => 'Slamet Riyadi', 'phone' => '081200000004', 'is_active' => true],
            ['name' => 'Mekanik Nonaktif', 'phone' => '081200000005', 'is_active' => false],
        ])->map(fn (array $row) => Mechanic::create($row));
    }

    private function seedServices(): Collection
    {
        return collect([
            ['name' => 'Ganti Oli Mesin Mobil', 'price' => 350000, 'est_minutes' => 30],
            ['name' => 'Servis Ringan', 'price' => 450000, 'est_minutes' => 60],
            ['name' => 'Tune Up', 'price' => 500000, 'est_minutes' => 120],
            ['name' => 'Ganti Kampas Rem Mobil', 'price' => 600000, 'est_minutes' => 90],
            ['name' => 'Servis AC Mobil', 'price' => 400000, 'est_minutes' => 90],
            ['name' => 'Balancing Roda', 'price' => 200000, 'est_minutes' => 60],
            ['name' => 'Spooring', 'price' => 350000, 'est_minutes' => 120],
            ['name' => 'Ganti Aki', 'price' => 850000, 'est_minutes' => 45],
            ['name' => 'Tambal Ban', 'price' => 30000, 'est_minutes' => 30],
            ['name' => 'Cuci Mesin', 'price' => 100000, 'est_minutes' => 45],
        ])->map(fn (array $row) => Service::create($row));
    }

    private function seedSpareparts(): Collection
    {
        return collect([
            ['sku' => 'OLI-001', 'name' => 'Oli Mesin 4L', 'price' => 280000, 'stock' => 24, 'min_stock' => 6],
            ['sku' => 'FLT-001', 'name' => 'Filter Oli', 'price' => 65000, 'stock' => 18, 'min_stock' => 5],
            ['sku' => 'FLT-002', 'name' => 'Filter Udara', 'price' => 95000, 'stock' => 12, 'min_stock' => 4],
            ['sku' => 'BRM-001', 'name' => 'Kampas Rem Depan', 'price' => 320000, 'stock' => 9, 'min_stock' => 4],
            ['sku' => 'BRM-002', 'name' => 'Kampas Rem Belakang', 'price' => 280000, 'stock' => 7, 'min_stock' => 4],
            ['sku' => 'AKI-001', 'name' => 'Aki Kering 60Ah', 'price' => 1250000, 'stock' => 5, 'min_stock' => 3],
            ['sku' => 'BSH-001', 'name' => 'Busi Iridium', 'price' => 120000, 'stock' => 16, 'min_stock' => 6],
            ['sku' => 'BLT-001', 'name' => 'V-Belt', 'price' => 210000, 'stock' => 8, 'min_stock' => 3],
            ['sku' => 'ACR-001', 'name' => 'Freon AC R134a', 'price' => 150000, 'stock' => 10, 'min_stock' => 4],
            ['sku' => 'CLN-001', 'name' => 'Cairan Pembersih Mesin', 'price' => 45000, 'stock' => 14, 'min_stock' => 5],
            ['sku' => 'TBN-001', 'name' => 'Tambalan Ban', 'price' => 25000, 'stock' => 30, 'min_stock' => 10],
            ['sku' => 'BOL-001', 'name' => 'Baut Roda Set', 'price' => 75000, 'stock' => 6, 'min_stock' => 4],
            ['sku' => 'LOW-001', 'name' => 'Wiper Blade 20 inci', 'price' => 90000, 'stock' => 2, 'min_stock' => 5],
            ['sku' => 'LOW-002', 'name' => 'Kunci Kontak Universal', 'price' => 130000, 'stock' => 1, 'min_stock' => 5],
            ['sku' => 'LOW-003', 'name' => 'Lampu Sein Depan', 'price' => 85000, 'stock' => 0, 'min_stock' => 4],
        ])->map(fn (array $row) => Sparepart::create($row));
    }

    private function seedCustomers(): Collection
    {
        return collect([
            ['name' => 'Darimin Rahman Setiawan', 'phone' => '081311110001', 'email' => 'darimin@example.test', 'address' => 'Jl. Melati 12, Bandung'],
            ['name' => 'Siti Nurhaliza', 'phone' => '081311110002', 'email' => 'siti@example.test', 'address' => 'Jl. Kenanga 4, Bandung'],
            ['name' => 'Andi Wijaya', 'phone' => '081311110003', 'email' => 'andi@example.test', 'address' => 'Jl. Cempaka 8, Cimahi'],
            ['name' => 'Rina Marlina', 'phone' => '081311110004', 'email' => null, 'address' => 'Jl. Anggrek 21, Bandung'],
            ['name' => 'Hendra Gunawan', 'phone' => '081311110005', 'email' => 'hendra@example.test', 'address' => 'Jl. Dahlia 3, Bandung'],
            ['name' => 'Maya Anggraini', 'phone' => '081311110006', 'email' => null, 'address' => 'Jl. Flamboyan 15, Bandung'],
            ['name' => 'Dedi Kurniawan', 'phone' => '081311110007', 'email' => 'dedi@example.test', 'address' => 'Jl. Mawar 9, Cimahi'],
            ['name' => 'Lestari Handayani', 'phone' => '081311110008', 'email' => 'lestari@example.test', 'address' => 'Jl. Kamboja 2, Bandung'],
            ['name' => 'Fajar Nugroho', 'phone' => '081311110009', 'email' => null, 'address' => 'Jl. Teratai 30, Bandung'],
            ['name' => 'Wulandari Putri', 'phone' => '081311110010', 'email' => 'wulan@example.test', 'address' => 'Jl. Sakura 7, Bandung'],
        ])->map(fn (array $row) => Customer::create($row));
    }

    private function seedVehicles(Collection $customers): Collection
    {
        $cars = [
            ['Toyota', 'Avanza', 2020],
            ['Honda', 'Brio', 2021],
            ['Daihatsu', 'Xenia', 2019],
            ['Suzuki', 'Ertiga', 2022],
            ['Toyota', 'Innova', 2018],
            ['Honda', 'Mobilio', 2020],
            ['Mitsubishi', 'Xpander', 2021],
            ['Nissan', 'Grand Livina', 2017],
            ['Toyota', 'Calya', 2019],
            ['Suzuki', 'Ignis', 2022],
            ['Honda', 'HR-V', 2023],
            ['Mazda', 'CX-5', 2020],
        ];

        return collect($cars)->map(fn (array $car, int $i) => Vehicle::create([
            'customer_id' => $customers[$i % $customers->count()]->id,
            'plate_number' => 'B'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT).'ABC',
            'type' => 'mobil',
            'brand' => $car[0],
            'model' => $car[1],
            'year' => $car[2],
        ]));
    }

    private function seedWorkOrders(Collection $vehicles, Collection $mechanics, Collection $services, Collection $parts): void
    {
        $orders = app(WorkOrderService::class);

        $ticket = fn (int $i, string $complaint = 'Pemeriksaan berkala') => $orders->register(
            $vehicles[$i], $complaint, $this->kasir
        );

        // Hari ini: menunggu, belum ada mekanik.
        $ticket(0, 'Rem berbunyi kasar saat mengerem');
        $ticket(1, 'AC tidak dingin sejak seminggu terakhir');

        // Hari ini: sedang dikerjakan, dengan lama pengerjaan berbeda.
        $working = [
            $ticket(2, 'Ganti oli dan filter'),
            $ticket(3, 'Servis ringan 40.000 km'),
            $ticket(4, 'Tune up mesin'),
        ];
        $orders->start($working[0], $mechanics[0]);
        // Kode yang mudah diingat untuk demo pelacakan publik.
        $working[0]->forceFill(['tracking_code' => 'TRACK234', 'started_at' => now()->subHours(3)])->save();
        $orders->addService($working[0], $services[0]);
        $orders->addPart($working[0], $parts[0], 1);

        $orders->start($working[1], $mechanics[1]);
        $working[1]->forceFill(['started_at' => now()->subMinutes(75)])->save();
        $orders->addService($working[1], $services[1]);

        $orders->start($working[2], $mechanics[2]);
        $working[2]->forceFill(['started_at' => now()->subMinutes(20)])->save();

        // Hari ini: selesai, menunggu pembayaran.
        $finished = $ticket(5, 'Kampas rem depan aus');
        $orders->start($finished, $mechanics[3]);
        $orders->addService($finished, $services[3]);
        $orders->addPart($finished, $parts[3], 1);
        $orders->finish($finished, 0);

        // Hari ini: sudah dibayar.
        $paidCash = $ticket(6, 'Servis AC rutin');
        $orders->start($paidCash, $mechanics[0]);
        $orders->addService($paidCash, $services[4]);
        $orders->addPart($paidCash, $parts[8], 1);
        $orders->finish($paidCash, 0);
        $orders->pay($paidCash, 'tunai', $paidCash->fresh()->total);

        $paidQris = $ticket(7, 'Spooring dan balancing');
        $orders->start($paidQris, $mechanics[1]);
        $orders->addService($paidQris, $services[6]);
        $orders->addService($paidQris, $services[5]);
        $orders->finish($paidQris, 25000);
        $orders->pay($paidQris, 'qris', $paidQris->fresh()->total);

        // Hari ini: dibatalkan.
        $orders->cancel($ticket(8, 'Minta perbaikan bodi, di luar layanan'));

        $backdate = function (WorkOrder $order, int $days, int $number): void {
            $order->forceFill(['queue_date' => today()->subDays($days), 'queue_number' => $number])->save();
        };

        // Riwayat beberapa hari sebelumnya untuk isi laporan.
        $yesterday = $ticket(10, 'Ganti oli berkala');
        $orders->start($yesterday, $mechanics[2]);
        $orders->addService($yesterday, $services[0]);
        $orders->addPart($yesterday, $parts[1], 1);
        $orders->finish($yesterday, 0);
        $orders->pay($yesterday, 'tunai', $yesterday->fresh()->total);
        $backdate($yesterday, 1, 1);

        $lastWeek = $ticket(10, 'Tune up dan ganti busi');
        $orders->start($lastWeek, $mechanics[0]);
        $orders->addService($lastWeek, $services[2]);
        $orders->addPart($lastWeek, $parts[6], 4);
        $orders->finish($lastWeek, 0);
        $orders->pay($lastWeek, 'transfer', $lastWeek->fresh()->total);
        $backdate($lastWeek, 5, 1);

        $lastMonth = $ticket(10, 'Servis ringan');
        $orders->start($lastMonth, $mechanics[0]);
        $orders->addService($lastMonth, $services[1]);
        $orders->finish($lastMonth, 0);
        $orders->pay($lastMonth, 'tunai', $lastMonth->fresh()->total);
        $backdate($lastMonth, 12, 1);

        // Mobil menginap: order kemarin yang belum selesai, tampil sebagai carry-over.
        $overnight = $ticket(8, 'Servis kopling, mobil ditinggal semalam');
        $orders->start($overnight, $mechanics[1]);
        $orders->addService($overnight, $services[2]);
        $overnight->forceFill(['started_at' => now()->subDay()->setTime(14, 0)])->save();
        $backdate($overnight, 1, 2);

        $oldCancel = $ticket(11, 'Batal karena pelanggan berubah pikiran');
        $orders->cancel($oldCancel);
        $backdate($oldCancel, 3, 1);
    }

    private function seedBookings(Collection $services): void
    {
        $bookings = app(BookingService::class);
        $tomorrow = today()->addDay()->toDateString();

        $create = fn (int $i, string $slot) => $bookings->create([
            'customer_name' => 'Booking Demo '.$i,
            'phone' => '0812345678'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'email' => $i <= 3 ? 'booking'.$i.'@bengkel.test' : null,
            'plate_number' => 'F'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'BOOK',
            'vehicle_type' => 'mobil',
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'service_id' => $services[0]->id,
            'complaint' => 'Booking demo '.$slot,
            'booking_date' => $tomorrow,
            'slot_time' => $slot,
        ]);

        // Slot 09:00 sengaja penuh untuk demo penolakan.
        $full = [$create(1, '09:00'), $create(2, '09:00'), $create(3, '09:00')];
        $create(4, '10:00');

        $confirmedA = $create(5, '11:00');
        $confirmedB = $create(6, '11:00');
        $bookings->receiveDp($confirmedA, 'transfer', $this->dpProof);
        $bookings->receiveDp($confirmedB, 'transfer', $this->dpProof);
        $bookings->confirm($confirmedA, $this->kasir);
        $bookings->confirm($confirmedB, $this->kasir);

        $bookings->cancel($create(7, '12:00'), $this->kasir);
        $create(8, '13:00');

        $this->seedTodayBookings($bookings, $services);

        // Booking lampau: tidak hadir dan dibatalkan.
        Booking::create([
            'booking_code' => 'LEWAT01',
            'customer_name' => 'Pelanggan Tidak Hadir',
            'phone' => '081399990001',
            'plate_number' => 'D9001XY',
            'vehicle_type' => 'mobil',
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'service_id' => $services[0]->id,
            'complaint' => 'Servis rutin',
            'booking_date' => today()->subDay()->toDateString(),
            'slot_time' => '09:00',
            'status' => 'tidak_hadir',
            'dp_amount' => 50000,
        ]);

        Booking::create([
            'booking_code' => 'LEWAT02',
            'customer_name' => 'Pelanggan Batal',
            'phone' => '081399990002',
            'plate_number' => 'D9002XY',
            'vehicle_type' => 'mobil',
            'brand' => 'Honda',
            'model' => 'Jazz',
            'service_id' => $services[1]->id,
            'complaint' => 'Servis ringan',
            'booking_date' => today()->subDays(2)->toDateString(),
            'slot_time' => '10:00',
            'status' => 'dibatalkan',
            'dp_amount' => 50000,
        ]);

        Booking::create([
            'booking_code' => 'LEWAT03',
            'customer_name' => 'Pelanggan Belum Datang',
            'phone' => '081399990003',
            'email' => 'belum.datang@example.test',
            'plate_number' => 'D9003LW',
            'vehicle_type' => 'mobil',
            'brand' => 'Suzuki',
            'model' => 'Swift',
            'service_id' => $services[1]->id,
            'complaint' => 'Servis ringan',
            'booking_date' => today()->subDay()->toDateString(),
            'slot_time' => '11:00',
            'status' => 'dikonfirmasi',
            'dp_amount' => 50000,
            'dp_paid_at' => now()->subDay(),
            'dp_method' => 'transfer',
            'dp_proof_path' => $this->dpProof,
            'dp_proof_at' => now()->subDay(),
        ]);

        // Kode yang mudah diingat untuk demo.
        $full[0]->update(['booking_code' => 'BKNG2345']);
    }

    private function seedTodayBookings(BookingService $bookings, Collection $services): void
    {
        $dp = (int) config('bengkel.booking.dp_amount');

        Booking::create([
            'booking_code' => 'HRINI01',
            'customer_name' => 'Pelanggan Baru Check-in',
            'phone' => '081399990010',
            'email' => 'baru.checkin@example.test',
            'plate_number' => 'D7777ZZ',
            'vehicle_type' => 'mobil',
            'brand' => 'Toyota',
            'model' => 'Rush',
            'service_id' => $services[1]->id,
            'complaint' => 'Servis ringan, pelanggan baru',
            'booking_date' => today()->toDateString(),
            'slot_time' => '14:00',
            'status' => 'dikonfirmasi',
            'dp_amount' => $dp,
            'dp_paid_at' => now()->subHours(2),
            'dp_method' => 'transfer',
            'dp_proof_path' => $this->dpProof,
            'dp_proof_at' => now()->subHours(2),
        ]);

        // Belum diverifikasi: staf masih perlu mengecek bukti QRIS.
        Booking::create([
            'booking_code' => 'HRINI03',
            'customer_name' => 'Sari Wulandari',
            'phone' => '081399990003',
            'email' => 'sari@example.test',
            'plate_number' => 'D9003XY',
            'vehicle_type' => 'mobil',
            'brand' => 'Daihatsu',
            'model' => 'Terios',
            'service_id' => $services[2]->id,
            'complaint' => 'Servis mendadak hari ini',
            'booking_date' => today()->toDateString(),
            'slot_time' => '15:00',
            'status' => 'menunggu',
            'dp_amount' => $dp,
            'dp_method' => 'qris',
            'dp_proof_path' => $this->dpProof,
            'dp_proof_at' => now()->subMinutes(40),
        ]);

        // Sudah check-in, jadi order hari ini memakai DP sebagai pembayaran awal.
        $checkedIn = Booking::create([
            'booking_code' => 'HRINI02',
            'customer_name' => 'Wulandari Putri',
            'phone' => '081311110010',
            'plate_number' => 'B010ABC',
            'vehicle_type' => 'mobil',
            'brand' => 'Suzuki',
            'model' => 'Ignis',
            'service_id' => $services[0]->id,
            'complaint' => 'Ganti oli, sudah check-in pagi',
            'booking_date' => today()->toDateString(),
            'slot_time' => '08:00',
            'status' => 'dikonfirmasi',
            'dp_amount' => $dp,
            'dp_paid_at' => now()->subHour(),
            'dp_method' => 'qris',
            'dp_proof_path' => $this->dpProof,
            'dp_proof_at' => now()->subHour(),
        ]);

        $order = $bookings->checkIn($checkedIn, $this->kasir);
        app(WorkOrderService::class)->start($order, Mechanic::where('is_active', true)->orderBy('id')->skip(2)->firstOrFail());
    }
}
