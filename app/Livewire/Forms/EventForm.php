<?php

namespace App\Livewire\Forms;

use App\Models\Setting;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/**
 * The event the system presents: title, subtitle and, when a new one is
 * picked, its image. Blank texts are saved as none.
 */
class EventForm extends Form
{
    public const int IMAGE_MAX_KILOBYTES = 5 * 1024;

    public string $title = '';

    public string $subtitle = '';

    public ?TemporaryUploadedFile $image = null;

    public function load(Setting $settings): void
    {
        $this->title = $settings->event_title ?? '';
        $this->subtitle = $settings->event_subtitle ?? '';
        $this->image = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            // No SVG: it can carry scripts, and it is shown to everyone.
            'image' => ['nullable', File::image()->types(['png', 'jpg', 'jpeg', 'webp'])->max(self::IMAGE_MAX_KILOBYTES)],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes): array
    {
        $attributes['title'] = Str::squish($attributes['title']);
        $attributes['subtitle'] = Str::squish($attributes['subtitle']);

        return $attributes;
    }
}
