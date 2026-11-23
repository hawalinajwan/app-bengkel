<?php

return [
    'max_active_orders_per_mechanic' => 2,

    'booking' => [
        'open_time' => '08:00',
        'close_time' => '16:00',
        'slot_minutes' => 60,
        'slot_capacity' => 3,
        'closed_weekdays' => ['Minggu'],
        'max_days_ahead' => 30,
        'dp_amount' => (int) env('BENGKEL_BOOKING_DP', 50000),
    ],
];
