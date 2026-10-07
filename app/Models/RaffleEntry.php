<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A teacher who took part in a raffle. The winners carry their position:
 * 1 for the first one revealed, 2 for the second… A winner who did not come
 * forward loses it and keeps it as the forfeited position, with when it was
 * declared and by whom.
 *
 * @property int $raffle_id
 * @property int $teacher_id
 * @property int|null $winner_position
 * @property int|null $forfeited_position
 * @property Carbon|null $forfeited_at
 * @property int|null $forfeited_by
 */
class RaffleEntry extends Pivot
{
    protected $table = 'raffle_entries';

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'winner_position' => 'integer',
            'forfeited_position' => 'integer',
            'forfeited_at' => 'datetime',
            'forfeited_by' => 'integer',
        ];
    }
}
