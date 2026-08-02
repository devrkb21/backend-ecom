<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', "not_in:{$categoryId}"],
            'is_active' => ['boolean'],
            'show_in_menu' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'show_in_menu' => $this->boolean('show_in_menu'),
            'sort_order' => $this->sort_order ?? 0,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parentId = $this->input('parent_id');
            $categoryId = $this->route('category')->id;

            if ($parentId === null || (int) $parentId === (int) $categoryId) {
                return; // "not_in" rule above already rejects direct self-parenting.
            }

            // Walk the proposed parent's ancestor chain — if this category
            // appears in it, applying parent_id would create an indirect
            // cycle (A -> B -> A), which hangs any code that walks the tree
            // (breadcrumbs, admin tree view) via an unbounded parent loop.
            $ancestorId = (int) $parentId;
            $visited = [];
            $depth = 0;

            while ($ancestorId && $depth < 100) {
                if ($ancestorId === (int) $categoryId) {
                    $validator->errors()->add('parent_id', 'This parent would create a category loop.');
                    return;
                }

                if (isset($visited[$ancestorId])) {
                    break; // Pre-existing cycle elsewhere in the tree — not this request's fault.
                }
                $visited[$ancestorId] = true;

                $ancestorId = (int) (Category::query()->whereKey($ancestorId)->value('parent_id') ?? 0);
                $depth++;
            }
        });
    }
}
