<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
class NotificationLogController extends Controller { public function index(): AnonymousResourceCollection { return NotificationLog::latest()->paginate(20); } }