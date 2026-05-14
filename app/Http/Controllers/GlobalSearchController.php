<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs the Cmd/Ctrl+K palette. Returns lightweight JSON only — no decrypted
 * data ever crosses this endpoint, so even a logged-in but compromised
 * session leaks just titles/usernames/URLs (the searchable surface area).
 */
class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (strlen($term) < 1) {
            return response()->json([]);
        }

        $results = Credential::with('category')
            ->search($term)
            ->limit(20)
            ->get()
            ->map(fn (Credential $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'subtitle' => $c->username ?: $c->email ?: $c->url,
                'category' => $c->category?->name,
                'category_icon' => $c->category?->icon ?? 'folder',
                'category_color' => $c->category?->color ?? '#71717A',
                'url' => route('credentials.show', $c),
            ]);

        return response()->json($results);
    }
}
