<?php

namespace App\Livewire\Forms;

use App\Models\City;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * Creates a city, or renames one when $city is set.
 */
class CityForm extends Form
{
    #[Locked]
    public ?City $city = null;

    public string $name = '';

    public function edit(City $city): void
    {
        $this->resetErrorBag();

        $this->city = $city;
        $this->name = $city->name;
    }

    public function isEditing(): bool
    {
        return $this->city !== null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(City::class)->ignore($this->city)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.unique' => __('There is already a municipality with that name.'),
        ];
    }

    /**
     * Stray spaces would otherwise let "Yopal " sneak past the unique rule.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes): array
    {
        $attributes['name'] = Str::squish($attributes['name']);

        return $attributes;
    }
}
