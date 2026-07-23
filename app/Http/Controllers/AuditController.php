<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Registro di audit (Amministrazione): accessi e modifiche.
 * Un admin non vede le attivita che coinvolgono i superadmin.
 */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();

        $query = Activity::query()->with(['causer', 'subject'])->latest();

        // Filtri
        if ($request->filled('log')) {
            $query->where('log_name', $request->input('log'));
        }
        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }
        if ($request->filled('causer')) {
            $query->where('causer_id', (int) $request->input('causer'))->where('causer_type', User::class);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        if ($request->filled('q')) {
            $query->where('description', 'ilike', '%'.$request->input('q').'%');
        }

        // Un admin non vede le attivita di/su superadmin
        if (! $actor->isSuperAdmin()) {
            $superIds = User::where('role', 'superadmin')->pluck('id')->all();
            if ($superIds) {
                $query->where(function ($w) use ($superIds) {
                    $w->whereNull('causer_id')
                        ->orWhere('causer_type', '!=', User::class)
                        ->orWhereNotIn('causer_id', $superIds);
                });
                $query->where(function ($w) use ($superIds) {
                    $w->whereNull('subject_id')
                        ->orWhere('subject_type', '!=', User::class)
                        ->orWhereNotIn('subject_id', $superIds);
                });
            }
        }

        $activities = $query->paginate(50)->withQueryString();

        $logNames = Activity::query()->select('log_name')->distinct()->whereNotNull('log_name')->pluck('log_name');
        $users = User::query()
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('role', '!=', 'superadmin'))
            ->orderBy('name')->get(['id', 'name']);

        return view('audit.index', compact('activities', 'logNames', 'users'));
    }
}
