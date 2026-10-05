<?php

namespace App\Livewire\Forms;

use App\Models\City;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * Registers a teacher, or edits one when $teacher is set. The municipality
 * is only there to narrow down the list of schools.
 */
class TeacherForm extends Form
{
    #[Locked]
    public ?Teacher $teacher = null;

    public string $name = '';

    public string $document_number = '';

    public string $code = '';

    public string $city_id = '';

    public string $school_id = '';

    public bool $is_union_member = true;

    public function edit(Teacher $teacher): void
    {
        $this->resetErrorBag();

        $this->teacher = $teacher;
        $this->name = $teacher->name;
        $this->document_number = $teacher->document_number;
        $this->code = $teacher->code;
        $this->city_id = (string) $teacher->school->city_id;
        $this->school_id = (string) $teacher->school_id;
        $this->is_union_member = $teacher->is_union_member;
    }

    public function isEditing(): bool
    {
        return $this->teacher !== null;
    }

    /**
     * The validated values that belong to the teacher itself.
     *
     * @param  array<string, mixed>  $validated
     * @return array{name: string, document_number: string, code: string, school_id: int, is_union_member: bool}
     */
    public function attributesFrom(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'document_number' => $validated['document_number'],
            'code' => $validated['code'],
            'school_id' => (int) $validated['school_id'],
            'is_union_member' => (bool) $validated['is_union_member'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document_number' => [
                'required',
                'digits_between:5,12',
                Rule::unique(Teacher::class)->ignore($this->teacher),
            ],
            'code' => [
                'required',
                'digits_between:1,10',
                Rule::unique(Teacher::class)->ignore($this->teacher),
            ],
            'city_id' => ['required', 'integer', Rule::exists(City::class, 'id')],
            'school_id' => [
                'required',
                'integer',
                Rule::exists(School::class, 'id')->where('city_id', $this->city_id),
            ],
            'is_union_member' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'document_number.unique' => __('There is already a teacher with that ID number, maybe among the retired ones.'),
            'code.unique' => __('There is already a teacher with that code, maybe among the retired ones.'),
        ];
    }

    /**
     * "1.118.541.203" and "1118541203" are the same ID number: only the
     * digits are kept. The code keeps its leading zeros: "0321" is not "321".
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes): array
    {
        $attributes['name'] = Str::squish($attributes['name']);
        $attributes['document_number'] = preg_replace('/[\s.\-]/', '', $attributes['document_number']);
        $attributes['code'] = preg_replace('/\s/', '', $attributes['code']);

        return $attributes;
    }
}
