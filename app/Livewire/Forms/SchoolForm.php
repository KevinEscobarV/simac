<?php

namespace App\Livewire\Forms;

use App\Models\City;
use App\Models\School;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * Creates a school, or edits one when $school is set. Editing can also move
 * it to another city.
 */
class SchoolForm extends Form
{
    #[Locked]
    public ?School $school = null;

    public string $name = '';

    public string $city_id = '';

    public function edit(School $school): void
    {
        $this->resetErrorBag();

        $this->school = $school;
        $this->name = $school->name;
        $this->city_id = (string) $school->city_id;
    }

    public function isEditing(): bool
    {
        return $this->school !== null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'city_id' => ['required', 'integer', Rule::exists(City::class, 'id')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(School::class)->where('city_id', $this->city_id)->ignore($this->school),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.unique' => __('That municipality already has a school with that name.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes): array
    {
        $attributes['name'] = Str::squish($attributes['name']);

        return $attributes;
    }
}
