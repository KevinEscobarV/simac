<?php

namespace App\Models;

use App\Enums\RaffleAnimation;
use Database\Factories\RaffleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * The record of a raffle ("acta"): what was raffled, among whom, who won and
 * whether the assembly had quorum at that moment. It is sealed before the
 * screen shows anything and never changes afterwards.
 *
 * @property int $id
 * @property int|null $assembly_id
 * @property string $prize
 * @property int $winners_count
 * @property RaffleAnimation $animation
 * @property array{union_members_only: bool, city_id: int|null, school_id: int|null, present_only: bool, exclude_previous_winners: bool} $filters
 * @property string $filter_description
 * @property int $participants_count
 * @property bool|null $quorum_met
 * @property int|null $drawn_by
 * @property Carbon $drawn_at
 * @property-read Assembly|null $assembly
 * @property-read User|null $drawer
 */
#[Fillable(['assembly_id', 'prize', 'winners_count', 'animation', 'filters', 'filter_description', 'participants_count', 'quorum_met', 'drawn_by'])]
class Raffle extends Model
{
    /** @use HasFactory<RaffleFactory> */
    use HasFactory;

    /** A record is written once: the moment it was drawn is all the time it keeps. */
    public const CREATED_AT = 'drawn_at';

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'animation' => RaffleAnimation::class,
            'filters' => 'array',
            'quorum_met' => 'boolean',
            'drawn_at' => 'datetime',
        ];
    }

    /**
     * The assembly in progress when it was drawn, if any.
     *
     * @return BelongsTo<Assembly, $this>
     */
    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function drawer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }

    /**
     * Everyone who was in the draw, retired teachers included: the record
     * keeps who took part.
     *
     * @return BelongsToMany<Teacher, $this, RaffleEntry>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'raffle_entries')
            ->using(RaffleEntry::class)
            ->withPivot('winner_position')
            ->withTrashed();
    }

    /**
     * The winners, in the order they are revealed.
     *
     * @return BelongsToMany<Teacher, $this, RaffleEntry>
     */
    public function winners(): BelongsToMany
    {
        return $this->participants()
            ->wherePivotNotNull('winner_position')
            ->orderByPivot('winner_position');
    }
}
