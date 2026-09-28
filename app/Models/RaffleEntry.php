<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A teacher who took part in a raffle. The winners carry their position:
 * 1 for the first one revealed, 2 for the second…
 *
 * @property int $raffle_id
 * @property int $teacher_id
 * @property int|null $winner_position
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
        ];
    }
}
