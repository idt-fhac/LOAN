<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::withCount(['deviceModels', 'devices'])
            ->orderBy('name')
            ->paginate(20);

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('create', Category::class);

        return view('categories.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        Category::create($data);

        return redirect()->route('categories.index')->with('success', __('Kategorie erstellt.'));
    }

    public function edit(Category $category)
    {
        $this->authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        return redirect()->route('categories.index')->with('success', __('Kategorie aktualisiert.'));
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        // Die Fremdschluesselregel wuerde das ohnehin verhindern - hier mit
        // verstaendlicher Meldung statt mit einer SQL-Exception.
        if ($category->deviceModels()->exists()) {
            return back()->with('error', __('Kategorie kann nicht gelöscht werden, es sind noch Geräte zugeordnet.'));
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', __('Kategorie gelöscht.'));
    }
}
