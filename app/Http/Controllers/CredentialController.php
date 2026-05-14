<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCredentialRequest;
use App\Http\Requests\UpdateCredentialRequest;
use App\Models\Category;
use App\Models\Credential;
use App\Models\PasswordHistory;
use App\Services\AuditService;
use App\Services\EncryptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CredentialController extends Controller
{
    public function __construct(
        private readonly EncryptionService $encryption,
        private readonly AuditService $audit,
    ) {}

    // ---------------------------------------------------------------------
    // List
    // ---------------------------------------------------------------------
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Credential::class);

        $categories = Category::orderBy('sort_order')->get();
        $categoryFilter = $request->query('category');
        $categoryId = $categoryFilter
            ? optional($categories->firstWhere('slug', $categoryFilter))->id
            : null;

        $query = Credential::with('category')
            ->inCategory($categoryId)
            ->search($request->query('q'))
            ->withTag($request->query('tag'));

        if ($request->boolean('favorites')) {
            $query->favorites();
        }

        $sort = match ($request->query('sort')) {
            'title' => ['title', 'asc'],
            'oldest' => ['created_at', 'asc'],
            'accessed' => ['last_accessed_at', 'desc'],
            'updated' => ['updated_at', 'desc'],
            default => ['created_at', 'desc'],
        };
        $query->orderBy($sort[0], $sort[1])->orderBy('id', 'desc');

        $credentials = $query->paginate(config('vault.pagination.credentials_per_page'))
            ->withQueryString();

        // Distinct tag list for filter pills.
        $allTags = Credential::whereNotNull('tags')->pluck('tags')->flatten()->unique()->sort()->values();

        return view('credentials.index', [
            'credentials' => $credentials,
            'categories' => $categories,
            'allTags' => $allTags,
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'category' => $categoryFilter,
                'tag' => $request->query('tag'),
                'favorites' => $request->boolean('favorites'),
                'sort' => $request->query('sort', 'recent'),
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // Create / Store
    // ---------------------------------------------------------------------
    public function create(Request $request): View
    {
        $this->authorize('create', Credential::class);

        $categories = Category::orderBy('sort_order')->get();

        return view('credentials.create', [
            'categories' => $categories,
            'preselectedCategoryId' => $request->query('category_id'),
        ]);
    }

    public function store(StoreCredentialRequest $request): RedirectResponse
    {
        $this->authorize('create', Credential::class);

        $credential = DB::transaction(function () use ($request) {
            return Credential::create([
                'category_id' => $request->integer('category_id'),
                'title' => $request->string('title'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password_encrypted' => $this->encryption->encrypt($request->input('password')),
                'url' => $request->input('url'),
                'notes_encrypted' => $this->encryption->encrypt($request->input('notes')),
                'custom_fields_encrypted' => $this->encryption->encryptJson($request->customFields()),
                'is_favorite' => $request->boolean('is_favorite'),
                'tags' => $request->tagsArray(),
                'password_changed_at' => now(),
            ]);
        });

        $this->audit->log('created', $request->user(), $credential, [
            'title' => $credential->title,
            'category_id' => $credential->category_id,
        ]);

        return redirect()->route('credentials.show', $credential)
            ->with('success', 'Credential saved.');
    }

    // ---------------------------------------------------------------------
    // Show
    // ---------------------------------------------------------------------
    public function show(Request $request, Credential $credential): View
    {
        $this->authorize('view', $credential);

        $credential->load(['category', 'passwordHistories' => fn ($q) => $q->limit(10)]);
        $auditTrail = $credential->auditLogs()->with('user')->latest('created_at')->limit(20)->get();

        // Mark accessed and audit (but only on initial GET, not refreshes within 5s).
        $recentlyTouched = $credential->last_accessed_at?->gt(now()->subSeconds(5));
        if (! $recentlyTouched) {
            $credential->forceFill(['last_accessed_at' => now()])->save();
            $this->audit->log('viewed', $request->user(), $credential);
        }

        // Decrypt non-secret-ish fields for display. Password is NOT decrypted here —
        // it's revealed only via the explicit /reveal endpoint so audit captures it.
        $notes = $this->encryption->decryptOrNull($credential->notes_encrypted);
        $customFields = $this->encryption->decryptJson($credential->custom_fields_encrypted);

        return view('credentials.show', compact('credential', 'auditTrail', 'notes', 'customFields'));
    }

    // ---------------------------------------------------------------------
    // Edit / Update
    // ---------------------------------------------------------------------
    public function edit(Credential $credential): View
    {
        $this->authorize('update', $credential);

        $credential->load('category');
        $categories = Category::orderBy('sort_order')->get();

        $notes = $this->encryption->decryptOrNull($credential->notes_encrypted);
        $customFields = $this->encryption->decryptJson($credential->custom_fields_encrypted);

        return view('credentials.edit', compact('credential', 'categories', 'notes', 'customFields'));
    }

    public function update(UpdateCredentialRequest $request, Credential $credential): RedirectResponse
    {
        $this->authorize('update', $credential);

        DB::transaction(function () use ($request, $credential) {
            $passwordChanged = false;

            $newPassword = $request->input('password');
            if (filled($newPassword)) {
                // Stash old password into history before overwriting.
                PasswordHistory::create([
                    'credential_id' => $credential->id,
                    'old_password_encrypted' => $credential->password_encrypted,
                    'changed_at' => now(),
                ]);
                $credential->password_encrypted = $this->encryption->encrypt($newPassword);
                $credential->password_changed_at = now();
                $passwordChanged = true;
            }

            $credential->fill([
                'category_id' => $request->integer('category_id'),
                'title' => $request->string('title'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'url' => $request->input('url'),
                'notes_encrypted' => $this->encryption->encrypt($request->input('notes')),
                'custom_fields_encrypted' => $this->encryption->encryptJson($request->customFields()),
                'is_favorite' => $request->boolean('is_favorite'),
                'tags' => $request->tagsArray(),
            ])->save();

            $this->audit->log('updated', $request->user(), $credential, [
                'password_changed' => $passwordChanged,
            ]);
        });

        return redirect()->route('credentials.show', $credential)
            ->with('success', 'Credential updated.');
    }

    // ---------------------------------------------------------------------
    // Delete
    // ---------------------------------------------------------------------
    public function destroy(Request $request, Credential $credential): RedirectResponse
    {
        $this->authorize('delete', $credential);

        $this->audit->log('deleted', $request->user(), $credential, ['title' => $credential->title]);
        $credential->delete();

        return redirect()->route('credentials.index')
            ->with('success', 'Credential moved to trash.');
    }

    // ---------------------------------------------------------------------
    // Favorite toggle
    // ---------------------------------------------------------------------
    public function toggleFavorite(Request $request, Credential $credential): JsonResponse
    {
        $this->authorize('update', $credential);

        $credential->is_favorite = ! $credential->is_favorite;
        $credential->save();

        return response()->json([
            'is_favorite' => $credential->is_favorite,
        ]);
    }

    // ---------------------------------------------------------------------
    // Reveal password (logs the access)
    // ---------------------------------------------------------------------
    public function reveal(Request $request, Credential $credential): JsonResponse
    {
        $this->authorize('reveal', $credential);

        $this->audit->log('revealed', $request->user(), $credential);

        return response()->json([
            'password' => $this->encryption->decryptOrNull($credential->password_encrypted),
            'reveal_seconds' => config('vault.password_reveal_seconds'),
        ]);
    }

    // ---------------------------------------------------------------------
    // Copy payload (returns plaintext + audits the copy)
    // ---------------------------------------------------------------------
    public function copyPayload(Request $request, Credential $credential): JsonResponse
    {
        $this->authorize('copy', $credential);

        $field = $request->input('field', 'password');
        $allowedFields = ['password', 'username', 'email', 'url'];

        if (! in_array($field, $allowedFields, true)) {
            abort(422, 'Unknown field.');
        }

        if ($field === 'password') {
            $this->audit->log('copied_password', $request->user(), $credential);
            $value = $this->encryption->decryptOrNull($credential->password_encrypted);
        } else {
            if ($field === 'username') {
                $this->audit->log('copied_username', $request->user(), $credential);
            }
            $value = $credential->{$field};
        }

        $credential->forceFill(['last_accessed_at' => now()])->save();

        return response()->json([
            'value' => $value,
            'clear_after' => $field === 'password' ? config('vault.clipboard_clear_seconds') : null,
        ]);
    }

    // ---------------------------------------------------------------------
    // Bulk operations
    // ---------------------------------------------------------------------
    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:credentials,id'],
        ]);

        $count = 0;
        DB::transaction(function () use ($validated, $request, &$count) {
            $credentials = Credential::whereIn('id', $validated['ids'])->get();
            foreach ($credentials as $cred) {
                $this->authorize('delete', $cred);
                $this->audit->log('deleted', $request->user(), $cred, ['title' => $cred->title, 'bulk' => true]);
                $cred->delete();
                $count++;
            }
        });

        return back()->with('success', "Deleted {$count} credentials.");
    }

    public function bulkExport(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:credentials,id'],
        ]);

        $credentials = Credential::with('category')
            ->whereIn('id', $validated['ids'])
            ->get();

        $filename = 'securevault-export-' . now()->format('Ymd-His') . '.csv';

        $this->audit->log('exported', $request->user(), null, [
            'count' => $credentials->count(),
        ]);

        return response()->streamDownload(function () use ($credentials) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Title', 'Category', 'Username', 'Email', 'URL', 'Password', 'Notes', 'Tags', 'Created']);

            foreach ($credentials as $c) {
                fputcsv($out, [
                    $c->title,
                    $c->category?->name,
                    $c->username,
                    $c->email,
                    $c->url,
                    $this->encryption->decryptOrNull($c->password_encrypted),
                    $this->encryption->decryptOrNull($c->notes_encrypted),
                    implode(',', $c->tags ?? []),
                    optional($c->created_at)->toIso8601String(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
