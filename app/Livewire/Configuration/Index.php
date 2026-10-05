<?php

namespace App\Livewire\Configuration;

use App\Livewire\Forms\EventForm;
use App\Models\Projection;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * How the system presents itself: the event the union is holding, shown on
 * the sign-in page, the home page and the projection screens, and what those
 * screens tell the room about each raffle. Screens already on show every
 * change at once.
 *
 * @property-read Setting $settings
 */
class Index extends Component
{
    use WithFileUploads;

    public EventForm $form;

    /** The projection screen's switches: each one is saved as soon as it is flipped. */
    public bool $screen_shows_membership = false;

    public bool $screen_shows_participants = true;

    public bool $screen_shows_filters = true;

    public function mount(): void
    {
        $this->form->load($this->settings);
        $this->fill($this->settings->only(Setting::SCREEN_OPTIONS));
    }

    #[Computed]
    public function settings(): Setting
    {
        return Setting::current();
    }

    public function updated(string $property): void
    {
        if (! in_array($property, Setting::SCREEN_OPTIONS, true)) {
            return;
        }

        $this->authorize('manage', Setting::class);

        $this->settings->update([$property => $this->{$property}]);
        Projection::current()->announce();

        Flux::toast(variant: 'success', text: __('Projection screen updated.'));
    }

    /**
     * A wrong file is pointed out as soon as it is picked, not on saving.
     */
    public function updatedFormImage(): void
    {
        $this->form->validateOnly('image');
    }

    public function save(): void
    {
        $this->authorize('manage', Setting::class);

        $validated = $this->form->validate();

        $this->settings->update([
            'event_title' => $validated['title'] ?: null,
            'event_subtitle' => $validated['subtitle'] ?: null,
        ]);

        if ($this->form->image !== null) {
            $this->settings->replaceEventImage($this->form->image);
        }

        $this->form->load($this->settings);
        Projection::current()->announce();

        Flux::toast(variant: 'success', text: __('Configuration saved.'));
    }

    public function removeImage(): void
    {
        $this->authorize('manage', Setting::class);

        $this->settings->removeEventImage();
        $this->form->image = null;
        Projection::current()->announce();

        Flux::toast(text: __('The event image was removed.'));
    }

    public function render(): View
    {
        return view('livewire.configuration.index')->title(__('Configuration'));
    }
}
