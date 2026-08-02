<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('categories')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', "not_in:{$categoryId}"],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parentId = $this->input('parent_id');
            $categoryId = (int) $this->route('category');

            if ($parentId === null || (int) $parentId === $categoryId) {
                return; // "not_in" rule above already rejects direct self-parenting.
            }

            // Walk the proposed parent's ancestor chain — if this category
            // appears in it, applying parent_id would create an indirect
            // cycle, which hangs any code that walks the tree unbounded.
            $ancestorId = (int) $parentId;
            $visited = [];
            $depth = 0;

            while ($ancestorId && $depth < 100) {
                if ($ancestorId === $categoryId) {
                    $validator->errors()->add('parent_id', 'This parent would create a category loop.');
                    return;
                }

                if (isset($visited[$ancestorId])) {
                    break;
                }
                $visited[$ancestorId] = true;

                $ancestorId = (int) (Category::query()->whereKey($ancestorId)->value('parent_id') ?? 0);
                $depth++;
            }
        });
    }
}
