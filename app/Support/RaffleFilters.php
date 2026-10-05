<?php

namespace App\Support;

use App\Models\Assembly;
use App\Models\City;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who enters a raffle. The same conditions count the participants while the
 * raffle is being set up and pick them when it is drawn.
 */
final readonly class RaffleFilters
{
    public function __construct(
        public bool $unionMembersOnly = false,
        public ?int $cityId = null,
        public ?int $schoolId = null,
        public bool $presentOnly = false,
        public bool $excludePreviousWinners = true,
    ) {}

    /**
     * @param  array{union_members_only?: bool, city_id?: int|null, school_id?: int|null, present_only?: bool, exclude_previous_winners?: bool}  $filters
     */
    public static function fromArray(array $filters): self
    {
        return new self(
            unionMembersOnly: $filters['union_members_only'] ?? false,
            cityId: $filters['city_id'] ?? null,
            schoolId: $filters['school_id'] ?? null,
            presentOnly: $filters['present_only'] ?? false,
            excludePreviousWinners: $filters['exclude_previous_winners'] ?? true,
        );
    }

    /**
     * @return array{union_members_only: bool, city_id: int|null, school_id: int|null, present_only: bool, exclude_previous_winners: bool}
     */
    public function toArray(): array
    {
        return [
            'union_members_only' => $this->unionMembersOnly,
            'city_id' => $this->cityId,
            'school_id' => $this->schoolId,
            'present_only' => $this->presentOnly,
            'exclude_previous_winners' => $this->excludePreviousWinners,
        ];
    }

    /**
     * The teachers who enter the draw: active ones only. "Present" needs an
     * open assembly, and so does leaving out who already won at it.
     *
     * @return Builder<Teacher>
     */
    public function participants(?Assembly $assembly): Builder
    {
        return Teacher::query()
            ->when($this->unionMembersOnly, fn (Builder $query) => $query->unionMembers())
            ->when($this->cityId !== null, fn (Builder $query) => $query->inCity((int) $this->cityId))
            ->when($this->schoolId !== null, fn (Builder $query) => $query->where('school_id', $this->schoolId))
            ->when($this->presentOnly, fn (Builder $query) => $assembly !== null
                ? $query->presentAt($assembly)
                : $query->whereRaw('1 = 0'))
            ->when($this->excludePreviousWinners && $assembly !== null, fn (Builder $query) => $query->wonAt($assembly, false));
    }

    /**
     * The conditions in words, as the record keeps them: names may change
     * later, the record does not.
     */
    public function describe(?Assembly $assembly): string
    {
        return $this->conditions($assembly, membership: true) ?? __('All teachers');
    }

    /**
     * The same words, leaving union membership out, for a projection screen
     * that keeps it to itself. A raffle only for members then has nothing to
     * say about who takes part: "all teachers" would not be true.
     */
    public function describeWithoutMembership(?Assembly $assembly): ?string
    {
        $conditions = $this->conditions($assembly, membership: false);

        if ($conditions !== null || $this->unionMembersOnly) {
            return $conditions;
        }

        return __('All teachers');
    }

    /**
     * The conditions that apply, joined, or null when there are none.
     */
    private function conditions(?Assembly $assembly, bool $membership): ?string
    {
        $conditions = collect([
            $this->presentOnly ? __('Only those present') : null,
            $membership && $this->unionMembersOnly ? __('Only union members') : null,
            $this->cityId !== null ? City::find($this->cityId)?->name : null,
            $this->schoolId !== null ? School::find($this->schoolId)?->name : null,
            $this->excludePreviousWinners && $assembly !== null ? __('Without earlier winners') : null,
        ])->filter();

        return $conditions->isEmpty() ? null : $conditions->implode(' · ');
    }
}
