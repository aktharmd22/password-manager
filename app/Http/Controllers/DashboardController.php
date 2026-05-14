<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalCredentials = Credential::count();

        $byCategory = Category::withCount('credentials')
            ->orderByDesc('credentials_count')
            ->get();

        $recentlyAccessed = Credential::with('category')
            ->whereNotNull('last_accessed_at')
            ->orderByDesc('last_accessed_at')
            ->limit(5)
            ->get();

        $recentlyAdded = Credential::with('category')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $favoritesCount = Credential::favorites()->count();

        $auditPreview = AuditLog::with(['user', 'credential'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'totalCredentials',
            'byCategory',
            'recentlyAccessed',
            'recentlyAdded',
            'favoritesCount',
            'auditPreview',
        ));
    }
}
