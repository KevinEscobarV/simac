<?php

namespace App\Models;

use App\Enums\QuorumType;
use App\Support\Quorum;
use Database\Factories\AssemblyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * An assembly of the union ("jornada" in the interface). Only one can be
 * open at a time: it is the one attendance is taken on and the one that
 * decides who is "present" in a raffle.
 *
 * @property int $id
 * @property string $name
 * @property Carbon $date
 * @property string|null $location
 * @property QuorumType|null $quorum_type
 * @property float|null $quorum_value
 * @property int|null $opened_by
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $attendances_count
 * @property-read int|null $present_count
 */
#[Fillable(['name', 'date', 'location', 'quorum_type', 'quorum_value', 'opened_by'])]
class Assembly extends Model
{
    /** @use HasFactory<AssemblyFactory> */
    use HasFactory;

    /**
     * Held while an assembly is opened or reopened, so two admins cannot end
     * up with two open at once.
     */
    public const string OPENING_LOCK = 'assemblies:opening';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'quorum_type' => QuorumType::class,
            'quorum_value' => 'float',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /**
     * A closed assembly keeps its history but takes no more attendance.
     *
     * @throws ValidationException
     */
    public function ensureOpen(): void
    {
        if (! $this->isOpen()) {
            throw ValidationException::withMessages([
                'assembly' => __('":name" is closed: it takes no more check-ins or check-outs.', ['name' => $this->name]),
            ]);
        }
    }

    /**
     * The assembly currently open, if any.
     */
    public static function current(): ?self
    {
        return static::query()->open()->latest('id')->first();
    }

    /**
     * The quorum as it stands right now, or null when none was set.
     */
    public function quorum(): ?Quorum
    {
        if ($this->quorum_type === null || $this->quorum_value === null) {
            return null;
        }

        return new Quorum(
            type: $this->quorum_type,
            value: $this->quorum_value,
            unionMembers: Teacher::query()->unionMembers()->count(),
            presentMembers: Teacher::query()->unionMembers()->presentAt($this)->count(),
        );
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->whereNotNull('closed_at');
    }

    /**
     * Registered and present counts, as `attendances_count` and `present_count`.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withAttendanceCounts(Builder $query): void
    {
        $query->withCount([
            'attendances',
            'attendances as present_count' => fn (Builder $attendances) => $attendances->whereNull('checked_out_at'),
        ]);
    }
}
