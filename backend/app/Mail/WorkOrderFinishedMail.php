<?php

namespace App\Mail;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class WorkOrderFinishedMail extends Mailable
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    public function build(): self
    {
        return $this->subject('Pesanan servis selesai')->view('emails.work-order-finished');
    }
}
