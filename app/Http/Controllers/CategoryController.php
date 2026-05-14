<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $categories = Category::withCount('credentials')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create', [
            'category' => new Category(['icon' => 'folder', 'color' => '#6366F1', 'sort_order' => Category::max('sort_order') + 1]),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());
        $this->audit->log('created', $request->user(), null, ['type' => 'category', 'name' => $category->name]);

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());
        $this->audit->log('updated', $request->user(), null, ['type' => 'category', 'name' => $category->name]);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->credentials()->exists()) {
            return back()->with('error', 'Cannot delete a category that still contains credentials. Move them first.');
        }

        $name = $category->name;
        $category->delete();
        $this->audit->log('deleted', request()->user(), null, ['type' => 'category', 'name' => $name]);

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
