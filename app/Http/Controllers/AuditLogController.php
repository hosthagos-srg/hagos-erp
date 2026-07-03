<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('modul')) {
            // cocokkan berdasarkan nama class pendek (basename)
            $query->where('auditable_type', 'like', '%\\' . $request->modul);
        }
        if ($request->filled('dari')) {
            $query->whereDate('created_at', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('created_at', '<=', $request->sampai);
        }

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(50)->withQueryString();

        // daftar modul yang muncul di log (untuk dropdown filter)
        $moduls = AuditLog::query()
            ->select('auditable_type')->distinct()->pluck('auditable_type')
            ->map(fn ($t) => class_basename($t))->unique()->sort()->values();

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('audit.index', compact('logs', 'moduls', 'users'));
    }
}
