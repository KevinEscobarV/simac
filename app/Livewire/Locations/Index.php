<?php

namespace App\Livewire\Locations;

use App\Actions\Locations\DeleteCity;
use App\Actions\Locations\DeleteSchool;
use App\Livewire\Forms\CityForm;
use App\Livewire\Forms\SchoolForm;
use App\Models\City;
use App\Models\School;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Municipalities on one side and the schools of the selected one on the
 * other. Searching looks for schools across every municipality.
 *
 * @property-read Collection<int, City> $cities
 * @property-read City|null $selectedCity
 * @property-read Collection<int, School> $schools
 */
class Index extends Component
{
    #[Url(as: 'municipio', except: null)]
    public ?int $cityId = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public CityForm $cityForm;

    public SchoolForm $schoolForm;

    /** The quick-add field under the list of schools. */
    public SchoolForm $newSchool;

    #[Locked]
    public ?City $deletingCity = null;

    #[Locked]
    public ?string $cityDeletionBlocker = null;

    #[Locked]
    public ?School $deletingSchool = null;

    #[Locked]
    public ?string $schoolDeletionBlocker = null;

    /**
     * @return Collection<int, City>
     */
    #[Computed]
    public function cities(): Collection
    {
        return City::query()->withCount(['schools', 'teachers'])->orderByName()->get();
    }

    /**
     * The city in the URL, or the first one when it is missing or gone.
     */
    #[Computed]
    public function selectedCity(): ?City
    {
        return $this->cities->firstWhere('id', $this->cityId) ?? $this->cities->first();
    }

    /**
     * @return Collection<int, School>
     */
    #[Computed]
    public function schools(): Collection
    {
        if ($this->search !== '') {
            return School::query()
                ->with('city')
                ->withCount('teachers')
                ->whereNameContains($this->search)
                ->orderByName()
                ->get();
        }

        if ($this->selectedCity === null) {
            return new Collection;
        }

        return $this->selectedCity->schools()->withCount('teachers')->orderByName()->get();
    }

    public function selectCity(int $cityId): void
    {
        $this->cityId = $cityId;
        $this->search = '';

        $this->newSchool->reset();
        $this->newSchool->resetErrorBag();
    }

    public function createCity(): void
    {
        $this->authorize('create', City::class);

        $this->cityForm->reset();
        $this->cityForm->resetErrorBag();

        Flux::modal('city-form')->show();
    }

    public function editCity(City $city): void
    {
        $this->authorize('update', $city);

        $this->cityForm->edit($city);

        Flux::modal('city-form')->show();
    }

    public function saveCity(): void
    {
        $editing = $this->cityForm->city;

        if ($editing) {
            $this->authorize('update', $editing);
        } else {
            $this->authorize('create', City::class);
        }

        $validated = $this->cityForm->validate();

        if ($editing) {
            $editing->update($validated);
        } else {
            $this->selectCity(City::create($validated)->id);
        }

        Flux::modal('city-form')->close();
        Flux::toast(variant: 'success', text: $editing ? __('Municipality updated.') : __('Municipality created.'));

        $this->cityForm->reset();
        $this->refreshLists();
    }

    public function confirmCityDeletion(City $city, DeleteCity $deleteCity): void
    {
        $this->authorize('delete', $city);

        $this->resetErrorBag('city');
        $this->deletingCity = $city;
        $this->cityDeletionBlocker = $deleteCity->blocker($city);

        Flux::modal('city-delete')->show();
    }

    public function deleteCity(DeleteCity $deleteCity): void
    {
        $this->authorize('delete', $this->deletingCity);

        $deleteCity->handle($this->deletingCity);

        Flux::modal('city-delete')->close();
        Flux::toast(text: __(':name was deleted.', ['name' => $this->deletingCity->name]));

        if ($this->cityId === $this->deletingCity->id) {
            $this->cityId = null;
        }

        $this->deletingCity = null;
        $this->refreshLists();
    }

    public function addSchool(): void
    {
        $this->authorize('create', School::class);

        $this->newSchool->city_id = (string) $this->selectedCity?->id;

        $school = School::create($this->newSchool->validate());

        Flux::toast(variant: 'success', text: __(':name was added.', ['name' => $school->name]));

        $this->newSchool->reset();
        $this->refreshLists();
    }

    public function editSchool(School $school): void
    {
        $this->authorize('update', $school);

        $this->schoolForm->edit($school);

        Flux::modal('school-form')->show();
    }

    public function saveSchool(): void
    {
        $school = $this->schoolForm->school;

        $this->authorize('update', $school);

        $validated = $this->schoolForm->validate();

        // A school that moves takes its teachers along, retired ones too: they work there.
        DB::transaction(function () use ($school, $validated): void {
            $school->update($validated);

            if ($school->wasChanged('city_id')) {
                $school->teachers()->withTrashed()->update(['city_id' => $school->city_id]);
            }
        });

        Flux::modal('school-form')->close();
        Flux::toast(variant: 'success', text: $school->wasChanged('city_id')
            ? __(':name is now in :city.', ['name' => $school->name, 'city' => $school->city->name])
            : __('School updated.'));

        $this->schoolForm->reset();
        $this->refreshLists();
    }

    public function confirmSchoolDeletion(School $school, DeleteSchool $deleteSchool): void
    {
        $this->authorize('delete', $school);

        $this->resetErrorBag('school');
        $this->deletingSchool = $school;
        $this->schoolDeletionBlocker = $deleteSchool->blocker($school);

        Flux::modal('school-delete')->show();
    }

    public function deleteSchool(DeleteSchool $deleteSchool): void
    {
        $this->authorize('delete', $this->deletingSchool);

        $deleteSchool->handle($this->deletingSchool);

        Flux::modal('school-delete')->close();
        Flux::toast(text: __(':name was deleted.', ['name' => $this->deletingSchool->name]));

        $this->deletingSchool = null;
        $this->refreshLists();
    }

    public function render(): View
    {
        return view('livewire.locations.index')->title(__('Municipalities and schools'));
    }

    /**
     * Computed properties are memoized for the whole request: forget them
     * after a write so the lists and their counts are read again.
     */
    private function refreshLists(): void
    {
        unset($this->cities, $this->selectedCity, $this->schools);
    }
}
