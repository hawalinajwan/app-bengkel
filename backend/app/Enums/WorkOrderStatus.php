<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Menunggu = 'menunggu';
    case Dikerjakan = 'dikerjakan';
    case Selesai = 'selesai';
    case Dibayar = 'dibayar';
    case Batal = 'batal';
}
