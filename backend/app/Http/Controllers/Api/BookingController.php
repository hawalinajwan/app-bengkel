<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\{BookingResource, PublicBookingResource, ServiceResource, WorkOrderResource};
use App\Models\{Booking, Service};
use App\Services\BookingService;
use Illuminate\Http\Request;
class BookingController extends Controller
{
    public function __construct(private readonly BookingService $service) {}
    public function index(Request $request) { $q=Booking::with(['service','workOrder'])->latest('booking_date')->latest('slot_time'); if($request->filled('status'))$q->where('status',$request->status); if($request->filled('date'))$q->whereDate('booking_date',$request->date); return BookingResource::collection($q->paginate(20)); }
    public function show(Booking $booking) { return new BookingResource($booking->load(['service','workOrder'])); }
    public function confirm(Booking $booking) { return new BookingResource($this->service->confirm($booking, auth()->user())); }
    public function reject(Request $request, Booking $booking) { $data=$request->validate(['staff_note'=>'required|string|max:2000']); return new BookingResource($this->service->reject($booking,auth()->user(),$data['staff_note'])); }
    public function cancel(Booking $booking) { return new BookingResource($this->service->cancel($booking,auth()->user())); }
    public function checkIn(Booking $booking) { return new WorkOrderResource($this->service->checkIn($booking,auth()->user())->load(['vehicle','serviceLines.service','partLines.sparepart'])); }
    public function noShow(Booking $booking) { return new BookingResource($this->service->markNoShow($booking,auth()->user())); }
    public function services() { return ServiceResource::collection(Service::query()->select(['id','name'])->orderBy('name')->get()); }
    public function slots(Request $request) { $data=$request->validate(['date'=>'required|date_format:Y-m-d']); return response()->json(['data'=>$this->service->availableSlots($data['date'])]); }
    public function publicCreate(BookingRequest $request) { return response()->json(['data'=>['booking_code'=>$this->service->create($request->validated())->booking_code]],201); }
    public function publicShow(string $code) { $b=Booking::with('workOrder')->where('booking_code',strtoupper($code))->firstOrFail(); return new PublicBookingResource($b); }
    public function publicCancel(string $code) { $b=Booking::where('booking_code',strtoupper($code))->firstOrFail(); return new PublicBookingResource($this->service->cancel($b)); }
}
