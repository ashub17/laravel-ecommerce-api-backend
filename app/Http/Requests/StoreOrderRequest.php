<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'same_as_shipping' => filter_var($this->input('same_as_shipping', true), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'shipping_address.full_name' => ['required', 'string', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:50'],
            'shipping_address.address_line1' => ['required', 'string', 'max:255'],
            'shipping_address.address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.state' => ['nullable', 'string', 'max:100'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:50'],
            'shipping_address.country' => ['required', 'string', 'max:100'],

            'same_as_shipping' => ['required', 'boolean'],

            'billing_address.full_name' => ['required_if:same_as_shipping,false', 'string', 'max:255'],
            'billing_address.phone' => ['required_if:same_as_shipping,false', 'string', 'max:50'],
            'billing_address.address_line1' => ['required_if:same_as_shipping,false', 'string', 'max:255'],
            'billing_address.address_line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_if:same_as_shipping,false', 'string', 'max:100'],
            'billing_address.state' => ['nullable', 'string', 'max:100'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:50'],
            'billing_address.country' => ['required_if:same_as_shipping,false', 'string', 'max:100'],
        ];
    }
}