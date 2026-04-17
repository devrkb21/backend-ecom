<?php

namespace App\Http\Requests\Admin;

use App\Models\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (Schema::hasTable('order_statuses') && OrderStatus::query()->where('is_active', true)->exists()) {
            return [
                'status' => [
                    'required',
                    'string',
                    Rule::exists('order_statuses', 'key')->where(function ($query) {
                        $query->where('is_active', true);
                    }),
                ],
            ];
        }

        return [
            'status' => ['required', 'string', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])],
        ];
    }
}
