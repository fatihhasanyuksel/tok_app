<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckpointStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // EnsureRole:admin already on routes
    }

    public function rules(): array
    {
        return [
            'key'           => ['required', 'string', 'alpha_dash', 'max:64', 'unique:checkpoint_stages,key'],
            'label'         => ['required', 'string', 'max:128'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }
}