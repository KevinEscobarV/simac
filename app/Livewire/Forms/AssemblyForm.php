<?php

namespace App\Livewire\Forms;

use App\Concerns\HasQuorumFields;
use App\Enums\QuorumType;
use Illuminate\Support\Str;
use Livewire\Form;

/**
 * Opens an assembly. It starts with the usual quorum of a union statute:
 * half of the members.
 */
class AssemblyForm extends Form
{
    use HasQuorumFields;

    public string $name = '';

    public string $date = '';

    public string $location = '';

    public function start(): void
    {
        $this->reset();
        $this->resetErrorBag();

        $this->date = today()->toDateString();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{name: string, date: string, location: string|null, quorum_type: QuorumType|null, quorum_value: float|null}
     */
    public function attributesFrom(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'date' => $validated['date'],
            'location' => $validated['location'] !== '' ? $validated['location'] : null,
            ...$this->quorumFrom($validated),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            'location' => ['nullable', 'string', 'max:255'],
            ...$this->quorumRules(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes): array
    {
        $attributes['name'] = Str::squish($attributes['name']);
        $attributes['location'] = Str::squish($attributes['location']);

        return $attributes;
    }
}
