<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = config('services.videos.max_size_kb', 512000);

        return [
            'video'           => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:' . $maxKb,
            'videourl'        => 'nullable|url',
            'courseid'        => 'required|string|max:100',
            'lessonid'        => 'required|string|max:100',
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'prerequisites'   => 'nullable|array',
            'prerequisites.*' => 'string',
            'resources'       => 'nullable|array',
            'resources.*'     => 'url',
            'videoId'         => 'nullable|string|max:100',
            'lessonDocId'     => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        $maxKb = config('services.videos.max_size_kb', 512000);

        return [
            'video.required'    => 'Please choose a video file.',
            'video.mimetypes'   => 'Allowed formats: MP4, MOV, AVI, WEBM.',
            'video.max'         => "Video is too large. Max {$maxKb} KB.",
            'courseid.required' => 'Course ID is required.',
            'lessonid.required' => 'Lesson ID is required.',
            'title.required'    => 'Title is required.',
        ];
    }
}
