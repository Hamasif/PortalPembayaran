<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with(['actor'])
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->action))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->q.'%';
                $query->where(fn ($q) => $q
                    ->where('entity_type', 'like', $term)
                    ->orWhereHas('actor', fn ($u) => $u->where('email', 'like', $term)));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $actions = AuditLog::distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actions' => $actions,
            'filters' => $request->only(['action', 'q']),
        ]);
    }
}
