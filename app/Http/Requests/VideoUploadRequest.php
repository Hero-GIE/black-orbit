<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // already gated by auth middleware
    }

    public function rules(): array
    {
        return [
            'video'       => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:' . env('VIDEO_MAX_SIZE', 512000), // 512 MB default
            'courseid'    => 'required|string|max:100',
            'lessonid'    => 'required|string|max:100',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'string',
            'resources'   => 'nullable|array',
            'resources.*' => 'url',
        ];
    }

    public function messages(): array
    {
        return [
            'video.required'  => 'Please choose a video file.',
            'video.mimetypes' => 'Allowed formats: MP4, MOV, AVI, WEBM.',
            'video.max'       => 'Video is too large. Max ' . env('VIDEO_MAX_SIZE', 512000) . ' KB.',
            'courseid.required' => 'Course ID is required.',
            'lessonid.required' => 'Lesson ID is required.',
            'title.required'    => 'Title is required.',
        ];
    }
}
