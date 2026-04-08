<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'required',
                Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            ],
            'payment_status' => [
                'sometimes',
                'required',
                Rule::in(['unpaid', 'paid', 'failed', 'refunded']),
            ],
        ];
    }
}