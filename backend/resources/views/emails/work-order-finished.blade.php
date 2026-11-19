<p>Pesanan servis kendaraan Anda telah selesai dikerjakan.</p>
<p>Nomor antrian: {{ $workOrder->queue_number }}</p>
<p>Total: Rp {{ number_format($workOrder->total, 0, ',', '.') }}</p>
