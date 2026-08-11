<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:' . env('IMAGE_MAX_SIZE', 10240),
            'folder' => 'sometimes|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Please provide an image file.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Allowed formats: JPEG, PNG, JPG, GIF, WEBP.',
            'image.max' => 'Image size must not exceed ' . env('IMAGE_MAX_SIZE', 10240) . ' KB.',
        ];
    }
}
