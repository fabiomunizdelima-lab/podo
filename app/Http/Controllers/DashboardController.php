<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->startOfDay();

        $stats = [
            'patients' => Patient::where('is_active', true)->count(),
            'today' => Appointment::whereDate('starts_at', $today)->count(),
            'week' => Appointment::whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        $todayAppointments = Appointment::with('patient')
            ->whereDate('starts_at', $today)
            ->whereNotIn('status', [AppointmentStatus::CANCELLED->value])
            ->orderBy('starts_at')
            ->get();

        return view('dashboard', compact('stats', 'todayAppointments'));
    }
}
