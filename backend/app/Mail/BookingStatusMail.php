<?php
namespace App\Mail;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
class BookingStatusMail extends Mailable
{
    use Queueable;
    public function __construct(public Booking $booking, public string $event) {}
    public function build(): self { return $this->subject('Pembaruan booking bengkel')->view('emails.booking-status'); }
}
