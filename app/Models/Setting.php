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
 * the event the union is holding (a title, a subtitle and its image), shown
 * on the sign-in page, the home page and the projection screens, and what
 * those screens tell the room about each raffle. An event spans several
 * assemblies, so it lives here and not on them. With no title and no image,
 * the system shows only SIMAC.
 *
 * @property int $id
 * @property string|null $event_title
 * @property string|null $event_subtitle
 * @property string|null $event_image_path
 * @property bool $screen_shows_membership
 * @property bool $screen_shows_participants
 * @property bool $screen_shows_filters
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['event_title', 'event_subtitle', 'screen_shows_membership', 'screen_shows_participants', 'screen_shows_filters'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /** The single row. */
    public const int ROW = 1;

    /** The public disk folder for the event's image. */
    public const string EVENT_FOLDER = 'event';

    /** What the projection screen may show or keep to itself, switched on and off from Configuration. */
    public const array SCREEN_OPTIONS = ['screen_shows_membership', 'screen_shows_participants', 'screen_shows_filters'];

    /**
     * The same defaults as the table, so a row just created already has them.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'screen_shows_membership' => false,
        'screen_shows_participants' => true,
        'screen_shows_filters' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'screen_shows_membership' => 'boolean',
            'screen_shows_participants' => 'boolean',
            'screen_shows_filters' => 'boolean',
        ];
    }

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
