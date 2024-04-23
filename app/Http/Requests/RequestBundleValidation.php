<?php

namespace App\Http\Requests;

use App\Rules\SlugRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RequestBundleValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required',
            'slug' => 'required',
            'bundle_thumbnail' => 'image|mimes:jpeg,png,jpg',
            'bundle_cover' => 'image|mimes:jpeg,png,jpg',
            'bundle_type' => 'required',
            'is_visible' => 'required',
            'is_featured' => 'required',
        ];

        // Exclude the current record's ID from unique validation rule in update method
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['slug'] = [
                'required',
                Rule::unique('bundles')->ignore($this->id),
            ];
        }else{
            $rules['slug'] = 'required|unique:bundles';
            $rules['bundle_thumbnail'] = 'required';    
        }

        return $rules;
    }
}
