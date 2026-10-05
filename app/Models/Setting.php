<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * How the system presents itself. A single row, edited from Configuration:
 * for now, the event the union is holding (a title, a subtitle and its
 * image), shown on the sign-in page, the home page and the projection
 * screens. An event spans several assemblies, so it lives here and not on
 * them. With no title and no image, the system shows only SIMAC.
 *
 * @property int $id
 * @property string|null $event_title
 * @property string|null $event_subtitle
 * @property string|null $event_image_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['event_title', 'event_subtitle'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /** The single row. */
    public const int ROW = 1;

    /** The public disk folder for the event's image. */
    public const string EVENT_FOLDER = 'event';

    public static function current(): self
    {
        return static::query()->find(self::ROW)
            ?? static::unguarded(fn (): self => static::query()->createOrFirst(['id' => self::ROW]));
    }

    public function hasEvent(): bool
    {
        return filled($this->event_title) || filled($this->event_image_path);
    }

    public function eventImageUrl(): ?string
    {
        return $this->event_image_path === null ? null : Storage::disk('public')->url($this->event_image_path);
    }

    /**
     * Stores the new image first and only then drops the old one, so the
     * event never ends up without a picture halfway.
     */
    public function replaceEventImage(UploadedFile $image): void
    {
        $previous = $this->event_image_path;

        $this->event_image_path = $image->store(self::EVENT_FOLDER, 'public') ?: null;
        $this->save();

        if ($previous !== null && $previous !== $this->event_image_path) {
            Storage::disk('public')->delete($previous);
        }
    }

    public function removeEventImage(): void
    {
        if ($this->event_image_path === null) {
            return;
        }

        Storage::disk('public')->delete($this->event_image_path);

        $this->event_image_path = null;
        $this->save();
    }
}
