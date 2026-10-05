<?php

namespace App\Livewire\Teachers;

use App\Livewire\Forms\SchoolForm;
use App\Livewire\Forms\TeacherForm;
use App\Models\City;
use App\Models\School;
use App\Models\Teacher;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The roll of teachers, with its filters in the URL. Retired teachers live
 * on their own tab, from where they can be brought back.
 *
 * @property-read Collection<int, City> $cities
 * @property-read Collection<int, School> $filterSchools
 * @property-read Collection<int, School> $formSchools
 * @property-read array{teachers: int, members: int, schools: int, cities: int, retired: int} $stats
 */
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'municipio', except: '')]
    public string $city = '';

    #[Url(as: 'colegio', except: '')]
    public string $school = '';

    /** "si", "no" or empty for everyone. */
    #[Url(as: 'afiliacion', except: '')]
    public string $membership = '';

    /** "activos" or "retirados". */
    #[Url(as: 'estado', except: 'activos')]
    public string $status = 'activos';

    public TeacherForm $form;

    /** A school created without leaving the teacher dialog. */
    public SchoolForm $newSchool;

    public bool $addingSchool = false;

    #[Locked]
    public ?Teacher $retiring = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'city', 'school', 'membership', 'status'], true)) {
            $this->resetPage();
        }

        if ($property === 'city') {
            $this->school = '';
        }

        if ($property === 'form.city_id') {
            $this->form->school_id = '';
            $this->cancelAddingSchool();
        }
    }

    /**
     * @return Collection<int, City>
     */
    #[Computed]
    public function cities(): Collection
    {
        return City::query()->orderByName()->get();
    }

    /**
     * @return Collection<int, School>
     */
    #[Computed]
    public function filterSchools(): Collection
    {
        return $this->schoolsIn($this->city);
    }

    /**
     * @return Collection<int, School>
     */
    #[Computed]
    public function formSchools(): Collection
    {
        return $this->schoolsIn($this->form->city_id);
    }

    /**
     * @return array{teachers: int, members: int, schools: int, cities: int, retired: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'teachers' => Teacher::count(),
            'members' => Teacher::query()->unionMembers()->count(),
            'schools' => School::has('teachers')->count(),
            'cities' => City::has('teachers')->count(),
            'retired' => Teacher::onlyTrashed()->count(),
        ];
    }

    public function showingRetired(): bool
    {
        return $this->status === 'retirados';
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->city !== '' || $this->school !== '' || $this->membership !== '';
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'city', 'school', 'membership');
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Teacher::class);

        $this->form->reset();
        $this->form->resetErrorBag();
        $this->cancelAddingSchool();

        // Registering from a filtered list usually means that same school.
        $this->form->city_id = $this->city;
        $this->form->school_id = $this->school;

        Flux::modal('teacher-form')->show();
    }

    public function edit(Teacher $teacher): void
    {
        $this->authorize('update', $teacher);

        $this->form->edit($teacher);
        $this->cancelAddingSchool();

        Flux::modal('teacher-form')->show();
    }

    public function save(): void
    {
        $editing = $this->form->teacher;

        if ($editing) {
            $this->authorize('update', $editing);
        } else {
            $this->authorize('create', Teacher::class);
        }

        $attributes = $this->form->attributesFrom($this->form->validate());

        if ($editing) {
            $editing->update($attributes);

            $message = __('Teacher updated.');
        } else {
            $teacher = Teacher::create($attributes);

            // The code is what they will be asked for at the desk.
            $message = __(':name was registered with code :code.', ['name' => $teacher->name, 'code' => $teacher->code]);
        }

        Flux::modal('teacher-form')->close();
        Flux::toast(variant: 'success', text: $message);

        $this->form->reset();
        unset($this->stats);
    }

    public function startAddingSchool(): void
    {
        $this->authorize('create', School::class);

        $this->addingSchool = true;
    }

    public function cancelAddingSchool(): void
    {
        $this->addingSchool = false;

        $this->newSchool->reset();
        $this->newSchool->resetErrorBag();
    }

    public function addSchool(): void
    {
        $this->authorize('create', School::class);

        $this->newSchool->city_id = $this->form->city_id;

        $school = School::create($this->newSchool->validate());

        $this->form->school_id = (string) $school->id;
        $this->cancelAddingSchool();
        unset($this->formSchools);

        Flux::toast(variant: 'success', text: __(':name was added to :city.', ['name' => $school->name, 'city' => $school->city->name]));
    }

    public function toggleMembership(Teacher $teacher): void
    {
        $this->authorize('update', $teacher);

        $teacher->update(['is_union_member' => ! $teacher->is_union_member]);

        Flux::toast(text: $teacher->is_union_member
            ? __(':name is now a union member.', ['name' => $teacher->name])
            : __(':name is no longer a union member.', ['name' => $teacher->name]));

        unset($this->stats);
    }

    public function confirmRetirement(Teacher $teacher): void
    {
        $this->authorize('delete', $teacher);

        $this->retiring = $teacher;

        Flux::modal('teacher-retire')->show();
    }

    public function retire(): void
    {
        $this->authorize('delete', $this->retiring);

        $this->retiring->delete();

        Flux::modal('teacher-retire')->close();
        Flux::toast(text: __(':name left the roll.', ['name' => $this->retiring->name]));

        $this->retiring = null;
        unset($this->stats);
    }

    public function reincorporate(int $teacherId): void
    {
        $teacher = Teacher::onlyTrashed()->findOrFail($teacherId);

        $this->authorize('restore', $teacher);

        $teacher->restore();

        Flux::toast(variant: 'success', text: __(':name is back on the roll.', ['name' => $teacher->name]));

        unset($this->stats);
    }

    public function render(): View
    {
        $teachers = Teacher::query()
            ->when($this->showingRetired(), fn (Builder $query) => $query->onlyTrashed())
            ->with(['school', 'city'])
            ->search($this->search)
            ->when($this->city !== '', fn (Builder $query) => $query->inCity((int) $this->city))
            ->when($this->school !== '', fn (Builder $query) => $query->where('school_id', (int) $this->school))
            ->when($this->membership !== '', fn (Builder $query) => $query->unionMembers($this->membership === 'si'))
            ->orderByName()
            ->paginate(25);

        return view('livewire.teachers.index', [
            'teachers' => $teachers,
        ])->title(__('Teachers'));
    }

    /**
     * @return Collection<int, School>
     */
    private function schoolsIn(string $cityId): Collection
    {
        if ($cityId === '') {
            return new Collection;
        }

        return School::query()->where('city_id', (int) $cityId)->orderByName()->get();
    }
}
