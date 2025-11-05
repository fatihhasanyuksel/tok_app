<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCheckpointStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $stage = $this->route('stage'); // implicit model binding
        $id    = $stage ? $stage->id : null;

        return [
            'key'           => ['sometimes', 'required', 'string', 'alpha_dash', 'max:64', Rule::unique('checkpoint_stages', 'key')->ignore($id)],
            'label'         => ['sometimes', 'required', 'string', 'max:128'],
            'display_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }
}