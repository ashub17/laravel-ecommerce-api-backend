<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContentBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('meta') && is_string($this->input('meta'))) {
            $decoded = json_decode($this->input('meta'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['meta' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        $contentBlock = $this->route('content_block');

        return [
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('content_blocks', 'key')->ignore($contentBlock?->id),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'meta' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}