<?php

namespace App\Livewire\Raffles;

use App\Models\Projection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The "LIVE" mark of the navigation: from any page, a raffle is on the
 * screens.
 */
class LiveBadge extends Component
{
    /**
     * Render again with the projection as it is now.
     */
    #[On('echo-private:projection,ProjectionUpdated')]
    #[On('projection-changed')]
    public function refreshLive(): void {}

    public function render(): View
    {
        return view('livewire.raffles.live-badge', [
            'live' => Projection::current()->isLive(),
        ]);
    }
}
