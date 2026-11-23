<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MechanicController;
use App\Http\Controllers\Api\NotificationLogController;
use App\Http\Controllers\Api\PublicTrackingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SparepartController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('throttle:30,1')->prefix('public')->group(function () {
    Route::get('/track/{code}', [PublicTrackingController::class, 'show']);
    Route::get('/services', [BookingController::class, 'services']);
    Route::get('/booking-slots', [BookingController::class, 'slots']);
    Route::post('/bookings', [BookingController::class, 'publicCreate']);
    Route::get('/bookings/{code}', [BookingController::class, 'publicShow']);
    Route::post('/bookings/{code}/cancel', [BookingController::class, 'publicCancel']);
    Route::post('/bookings/{code}/dp', [BookingController::class, 'publicDpProof']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/notification-logs', [NotificationLogController::class, 'index'])->middleware('role:admin');
    Route::get('/dashboard', DashboardController::class)->middleware('role:admin,kasir');
    Route::get('/reports/daily', [ReportController::class, 'daily'])->middleware('role:admin');
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
    Route::post('/bookings/{booking}/no-show', [BookingController::class, 'noShow']);
    Route::post('/bookings/{booking}/dp', [BookingController::class, 'receiveDp']);
    Route::get('/bookings/{booking}/dp-proof', [BookingController::class, 'dpProof']);
    Route::get('/work-orders/board', [WorkOrderController::class, 'board']);
    Route::get('/work-orders', [WorkOrderController::class, 'index']);
    Route::post('/work-orders', [WorkOrderController::class, 'store']);
    Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show']);
    Route::post('/work-orders/{workOrder}/start', [WorkOrderController::class, 'start']);
    Route::post('/work-orders/{workOrder}/services', [WorkOrderController::class, 'addService']);
    Route::delete('/work-orders/{workOrder}/services/{service}', [WorkOrderController::class, 'removeService']);
    Route::post('/work-orders/{workOrder}/parts', [WorkOrderController::class, 'addPart']);
    Route::patch('/work-orders/{workOrder}/parts/{part}', [WorkOrderController::class, 'updatePartQty']);
    Route::delete('/work-orders/{workOrder}/parts/{part}', [WorkOrderController::class, 'removePart']);
    Route::post('/work-orders/{workOrder}/finish', [WorkOrderController::class, 'finish']);
    Route::post('/work-orders/{workOrder}/pay', [WorkOrderController::class, 'pay']);
    Route::post('/work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel']);
    Route::patch('/work-orders/{workOrder}/public-note', [WorkOrderController::class, 'publicNote']);
    Route::apiResource('customers', CustomerController::class);
    Route::get('vehicles/{vehicle}/history', [VehicleController::class, 'history']);
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('mechanics', MechanicController::class)->only(['index', 'show']);
    Route::apiResource('services', ServiceController::class)->only(['index', 'show']);
    Route::apiResource('spareparts', SparepartController::class)->only(['index', 'show']);
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('mechanics', MechanicController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('services', ServiceController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('spareparts',SparepartController::class)->only(['store', 'update', 'destroy']);
    });
});
