<?php

namespace App\Livewire\Forms;

use App\Enums\RaffleAnimation;
use App\Models\City;
use App\Models\School;
use App\Support\RaffleFilters;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Livewire\Form;

/**
 * Sets up a raffle: who takes part, what is raffled and how many win, and how
 * the screen presents it.
 */
class RaffleForm extends Form
{
    /** More winners than this belongs to another kind of event. */
    public const int MAX_WINNERS = 20;

    public bool $union_members_only = false;

    public bool $present_only = false;

    public string $city_id = '';

    public string $school_id = '';

    public bool $exclude_previous_winners = true;

    public string $prize = '';

    public string $winners_count = '1';

    public string $animation = 'wheel';

    /**
     * The filters as they stand, validated or not: the summary counts the
     * participants while the form is being filled in.
     */
    public function filters(): RaffleFilters
    {
        return new RaffleFilters(
            unionMembersOnly: $this->union_members_only,
            cityId: ctype_digit($this->city_id) ? (int) $this->city_id : null,
            schoolId: ctype_digit($this->school_id) ? (int) $this->school_id : null,
            presentOnly: $this->present_only,
            excludePreviousWinners: $this->exclude_previous_winners,
        );
    }

    /**
     * Keep who takes part and how it is shown, for the next raffle of the
     * assembly; the prize is always a new one.
     */
    public function forNextRaffle(): void
    {
        $this->reset('prize', 'winners_count');
        $this->resetErrorBag();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'union_members_only' => ['boolean'],
            'present_only' => ['boolean'],
            'city_id' => ['nullable', 'integer', Rule::exists(City::class, 'id')],
            'school_id' => [
                'nullable',
                'integer',
                Rule::exists(School::class, 'id')->when($this->city_id !== '', fn (Exists $rule) => $rule->where('city_id', $this->city_id)),
            ],
            'exclude_previous_winners' => ['boolean'],
            'prize' => ['required', 'string', 'max:120'],
            'winners_count' => ['required', 'integer', 'min:1', 'max:'.self::MAX_WINNERS],
            'animation' => ['required', Rule::enum(RaffleAnimation::class)],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes): array
    {
        $attributes['prize'] = Str::squish($attributes['prize']);

        return $attributes;
    }
}
