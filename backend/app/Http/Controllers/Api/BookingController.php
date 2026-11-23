<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PublicBookingResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\WorkOrderResource;
use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $service) {}

    public function index(Request $request)
    {
        $q = Booking::with(['service', 'workOrder'])->latest('booking_date')->latest('slot_time');
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $q->whereDate('booking_date', $request->date);
        }

        return BookingResource::collection($q->paginate(20));
    }

    public function show(Booking $booking)
    {
        return new BookingResource($booking->load(['service', 'workOrder']));
    }

    public function confirm(Booking $booking)
    {
        return new BookingResource($this->service->confirm($booking, auth()->user()));
    }

    public function reject(Request $request, Booking $booking)
    {
        $data = $request->validate(['staff_note' => 'required|string|max:2000']);

        return new BookingResource($this->service->reject($booking, auth()->user(), $data['staff_note']));
    }

    public function cancel(Booking $booking)
    {
        return new BookingResource($this->service->cancel($booking, auth()->user()));
    }

    public function checkIn(Booking $booking)
    {
        return new WorkOrderResource($this->service->checkIn($booking, auth()->user())->load(['vehicle', 'serviceLines.service', 'partLines.sparepart']));
    }

    public function noShow(Booking $booking)
    {
        return new BookingResource($this->service->markNoShow($booking, auth()->user()));
    }

    public function receiveDp(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'method' => 'required|in:transfer,qris',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ], [
            'method.required' => 'Pilih metode DP.',
            'method.in' => 'DP hanya bisa dibayar transfer atau QRIS.',
            'proof.mimes' => 'Bukti harus berupa gambar (JPG/PNG/WEBP) atau PDF.',
            'proof.max' => 'Ukuran bukti maksimal 4 MB.',
        ]);
        $path = $request->hasFile('proof') ? $request->file('proof')->store('dp-proofs', 'local') : null;
        try {
            return new BookingResource($this->service->receiveDp($booking, $data['method'], $path));
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    public function publicDpProof(Request $request, string $code)
    {
        $booking = Booking::where('booking_code', strtoupper($code))->firstOrFail();
        $data = $request->validate([
            'method' => 'required|in:transfer,qris',
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ], [
            'method.required' => 'Pilih metode pembayaran DP.',
            'method.in' => 'DP hanya bisa dibayar transfer atau QRIS.',
            'proof.required' => 'Bukti transfer/QRIS wajib diunggah.',
            'proof.mimes' => 'Bukti harus berupa gambar (JPG/PNG/WEBP) atau PDF.',
            'proof.max' => 'Ukuran bukti maksimal 4 MB.',
        ]);
        $path = $request->file('proof')->store('dp-proofs', 'local');
        try {
            return new PublicBookingResource($this->service->submitDpProof($booking, $data['method'], $path));
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }

    public function dpProof(Booking $booking)
    {
        abort_unless($booking->dp_proof_path && Storage::disk('local')->exists($booking->dp_proof_path), 404);

        return Storage::disk('local')->response($booking->dp_proof_path);
    }

    public function services()
    {
        return ServiceResource::collection(Service::query()->select(['id', 'name', 'price', 'est_minutes'])->orderBy('name')->get());
    }

    public function slots(Request $request)
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d']);

        return response()->json(['data' => $this->service->availableSlots($data['date']), 'meta' => ['dp_amount' => (int) config('bengkel.booking.dp_amount')]]);
    }

    public function publicCreate(BookingRequest $request)
    {
        return response()->json(['data' => ['booking_code' => $this->service->create($request->validated())->booking_code]], 201);
    }

    public function publicShow(string $code)
    {
        $b = Booking::with('workOrder')->where('booking_code', strtoupper($code))->firstOrFail();

        return new PublicBookingResource($b);
    }

    public function publicCancel(string $code)
    {
        $b = Booking::where('booking_code', strtoupper($code))->firstOrFail();
        abort_if($b->dp_paid_at, 422, 'DP sudah dibayar. Hubungi bengkel untuk pembatalan.');

        return new PublicBookingResource($this->service->cancel($b));
    }
}
