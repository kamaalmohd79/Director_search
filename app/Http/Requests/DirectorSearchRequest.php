<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DirectorSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'nullable|string|max:100',
            'surname'    => 'required|string|max:100',
            'postcode'   => 'nullable|string|max:20',
            'start_index'=> 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return ['surname.required' => 'Please enter a surname.'];
    }
}
