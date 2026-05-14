<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with(['user', 'credential'])
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->filled('credential_id'), fn ($q) => $q->where('credential_id', $request->integer('credential_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->date('to')->endOfDay()))
            ->latest('created_at')
            ->paginate(config('vault.pagination.audit_logs_per_page'))
            ->withQueryString();

        $actionCounts = AuditLog::selectRaw('action, COUNT(*) as c')->groupBy('action')->pluck('c', 'action');
        $credentials = Credential::orderBy('title')->get(['id', 'title']);

        return view('audit.index', [
            'logs' => $logs,
            'actions' => config('vault.audit_actions'),
            'actionCounts' => $actionCounts,
            'credentials' => $credentials,
            'filters' => [
                'action' => $request->input('action'),
                'credential_id' => $request->input('credential_id'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = AuditLog::with(['user', 'credential'])
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->filled('credential_id'), fn ($q) => $q->where('credential_id', $request->integer('credential_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->date('to')->endOfDay()))
            ->latest('created_at');

        $filename = 'securevault-audit-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Timestamp (UTC)', 'User', 'Action', 'Credential', 'IP', 'User-Agent', 'Metadata']);

            // chunkById to keep memory flat on large exports.
            $query->chunkById(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->created_at->toIso8601String(),
                        $log->user?->email,
                        $log->action,
                        $log->credential?->title,
                        $log->ip_address,
                        $log->user_agent,
                        $log->metadata ? json_encode($log->metadata) : '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
