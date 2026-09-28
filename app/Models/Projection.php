<?php

namespace App\Models;

use App\Enums\ProjectionPhase;
use App\Events\ProjectionUpdated;
use Closure;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * What the projection screens show. A single row on the server, shared by
 * all of them: a screen turned on late, reloaded, or a second one, shows the
 * same, and a restart loses nothing.
 *
 * The phases go idle → ready ("GET READY") → animating → winner. With several
 * winners, each "Go!" from the winner phase animates the next one. Every
 * launch raises the attempt, which is what restarts the animation.
 *
 * @property int $id
 * @property ProjectionPhase $phase
 * @property int|null $raffle_id
 * @property int $attempt
 * @property int $winner_position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Raffle|null $raffle
 */
#[Fillable(['phase', 'raffle_id', 'attempt', 'winner_position'])]
class Projection extends Model
{
    /** The single row. */
    public const int ROW = 1;

    /**
     * A screen that stops reporting for this long no longer counts as on. A
     * screen reports every 15 seconds, but browsers slow down the timers of a
     * tab in the background to once a minute: the margin covers that. A
     * screen that closes says so, and stops counting at once.
     */
    public const int SCREEN_TIMEOUT = 75;

    private const string SCREENS_KEY = 'projection:screens';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phase' => ProjectionPhase::class,
            'attempt' => 'integer',
            'winner_position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Raffle, $this>
     */
    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    /**
     * The projection, created at rest the first time it is needed.
     */
    public static function current(): self
    {
        return static::query()->find(self::ROW)
            ?? static::unguarded(fn (): self => static::query()->createOrFirst(
                ['id' => self::ROW],
                ['phase' => ProjectionPhase::Idle, 'attempt' => 0, 'winner_position' => 0],
            ));
    }

    /**
     * Whether something is loaded on the screens.
     */
    public function isLive(): bool
    {
        return $this->phase !== ProjectionPhase::Idle;
    }

    /**
     * How many winners are public: the ones before the current and, once its
     * animation ended, the current one.
     */
    public function revealed(): int
    {
        return match ($this->phase) {
            ProjectionPhase::Idle => 0,
            ProjectionPhase::Winner => $this->winner_position,
            default => max(0, $this->winner_position - 1),
        };
    }

    /**
     * How many winners of a raffle are public: all of them, except for the
     * raffle on the screens, whose winners come out one by one.
     */
    public function revealedOf(Raffle $raffle): int
    {
        return $this->raffle_id === $raffle->id ? $this->revealed() : $raffle->winners_count;
    }

    /**
     * Whether winners of the loaded raffle are still to be revealed after the
     * current one.
     */
    public function hasWinnersLeft(): bool
    {
        return $this->raffle !== null && $this->winner_position < $this->raffle->winners_count;
    }

    /**
     * Load a raffle that was just drawn, without starting it: the room gets
     * ready while the first winner waits.
     *
     * @throws ValidationException when another raffle is still on screen
     */
    public function prepare(Raffle $raffle): void
    {
        $this->change(function (self $projection) use ($raffle): void {
            if ($projection->phase !== ProjectionPhase::Idle) {
                throw ValidationException::withMessages([
                    'projection' => __('There is a raffle on screen. Release it before drawing another one.'),
                ]);
            }

            $projection->fill([
                'phase' => ProjectionPhase::Ready,
                'raffle_id' => $raffle->id,
                'attempt' => 0,
                'winner_position' => 1,
            ]);
        });
    }

    /**
     * "Go!": animates the loaded winner or, once one is on screen, the next.
     *
     * @throws ValidationException when there is nothing to launch, or no screen to show it
     */
    public function launch(): void
    {
        $this->change(function (self $projection): void {
            if ($projection->phase === ProjectionPhase::Winner && $projection->hasWinnersLeft()) {
                $projection->winner_position++;
            } elseif ($projection->phase !== ProjectionPhase::Ready) {
                throw ValidationException::withMessages(['projection' => match ($projection->phase) {
                    ProjectionPhase::Idle => __('No raffle is loaded on the screen.'),
                    ProjectionPhase::Animating => __('The animation is already running.'),
                    default => __('Every winner has been revealed.'),
                }]);
            }

            self::ensureScreenConnected();

            $projection->phase = ProjectionPhase::Animating;
            $projection->attempt++;
        });
    }

    /**
     * "Again": the same winner from the start. The record does not change.
     *
     * @throws ValidationException when nothing was launched yet, or no screen is on
     */
    public function repeat(): void
    {
        $this->change(function (self $projection): void {
            if (! in_array($projection->phase, [ProjectionPhase::Animating, ProjectionPhase::Winner], true)) {
                throw ValidationException::withMessages(['projection' => $projection->phase === ProjectionPhase::Idle
                    ? __('No raffle is loaded on the screen.')
                    : __('There is nothing to repeat yet: launch it first.')]);
            }

            self::ensureScreenConnected();

            $projection->phase = ProjectionPhase::Animating;
            $projection->attempt++;
        });
    }

    /**
     * A screen reached the end of the animation, so the winner is public. A
     * screen lagging behind may report an attempt that was already replaced:
     * that report is ignored.
     *
     * @return bool whether the report was the current one
     */
    public function finish(int $attempt): bool
    {
        return $this->change(function (self $projection) use ($attempt): void {
            if ($projection->phase === ProjectionPhase::Animating && $projection->attempt === $attempt) {
                $projection->phase = ProjectionPhase::Winner;
            }
        });
    }

    /**
     * Back to rest. The record stays in the history.
     */
    public function release(): void
    {
        $this->change(function (self $projection): void {
            $projection->fill([
                'phase' => ProjectionPhase::Idle,
                'raffle_id' => null,
                'attempt' => 0,
                'winner_position' => 0,
            ]);
        });
    }

    /**
     * A screen reports that it is on; it does so every few seconds. The first
     * report of a screen is announced, so the console counts it right away.
     */
    public static function recordScreen(string $screen): void
    {
        $isNew = Cache::lock(self::SCREENS_KEY.':lock', 5)->block(3, function () use ($screen): bool {
            $screens = self::liveScreens();
            $isNew = ! array_key_exists($screen, $screens);

            $screens[$screen] = now()->getTimestamp();
            Cache::put(self::SCREENS_KEY, $screens, self::SCREEN_TIMEOUT * 2);

            return $isNew;
        });

        if ($isNew) {
            self::current()->announce();
        }
    }

    /**
     * A screen was closed or reloaded: it stops counting at once, and the
     * console hears about it. A reloaded screen reports again as it opens.
     */
    public static function forgetScreen(string $screen): void
    {
        $wasOn = Cache::lock(self::SCREENS_KEY.':lock', 5)->block(3, function () use ($screen): bool {
            $screens = self::liveScreens();
            $wasOn = array_key_exists($screen, $screens);

            unset($screens[$screen]);
            Cache::put(self::SCREENS_KEY, $screens, self::SCREEN_TIMEOUT * 2);

            return $wasOn;
        });

        if ($wasOn) {
            self::current()->announce();
        }
    }

    /**
     * How many screens reported in the last SCREEN_TIMEOUT seconds.
     */
    public static function connectedScreens(): int
    {
        return count(self::liveScreens());
    }

    /**
     * An animation launched with no screen on would run for nobody, and the
     * console would wait for an end that no screen reports.
     *
     * @throws ValidationException
     */
    private static function ensureScreenConnected(): void
    {
        if (self::connectedScreens() === 0) {
            throw ValidationException::withMessages([
                'projection' => __('No screen is connected. Open the projection screen, or reload it, to launch.'),
            ]);
        }
    }

    /**
     * @return array<string, int> last report of each screen, by screen id
     */
    private static function liveScreens(): array
    {
        $cutoff = now()->getTimestamp() - self::SCREEN_TIMEOUT;

        /** @var array<string, int> $screens */
        $screens = Cache::get(self::SCREENS_KEY, []);

        return array_filter($screens, fn (int $seen): bool => $seen >= $cutoff);
    }

    /**
     * Apply a change on the locked row, so the console and the screens never
     * overwrite each other, and tell every screen when something changed.
     *
     * @param  Closure(self): void  $change
     * @return bool whether something changed
     */
    private function change(Closure $change): bool
    {
        $changed = DB::transaction(function () use ($change): bool {
            $projection = static::query()->lockForUpdate()->findOrFail($this->id);

            $change($projection);

            $changed = $projection->isDirty();
            $projection->save();

            $this->setRawAttributes($projection->getAttributes(), true);
            $this->unsetRelation('raffle');

            return $changed;
        });

        if ($changed) {
            $this->announce();
        }

        return $changed;
    }

    private function announce(): void
    {
        broadcast(new ProjectionUpdated($this->phase, $this->attempt, $this->raffle_id, $this->winner_position))->toOthers();
    }
}
