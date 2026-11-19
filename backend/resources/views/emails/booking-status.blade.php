<p>Status booking {{ $booking->booking_code }}: {{ $booking->status }}.</p>
<p>Tanggal: {{ $booking->booking_date->format('d-m-Y') }}, pukul {{ $booking->slot_time }}.</p>
