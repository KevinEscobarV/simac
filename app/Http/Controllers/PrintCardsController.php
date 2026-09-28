<?php

namespace App\Http\Controllers;

use App\Support\CardSelection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintCardsController extends Controller
{
    /**
     * The sheets to print, letter size with eight cards each: a whole batch
     * or a single teacher's card. The browser prints them.
     */
    public function __invoke(Request $request): View
    {
        $selection = CardSelection::fromQuery($request->query());

        $teachers = $selection->teachers()->get();

        // A single card of someone retired, or who does not exist, is not a batch that came out empty.
        abort_if($selection->teacherId !== null && $teachers->isEmpty(), 404);

        return view('print.cards', [
            'sheets' => $teachers->chunk(CardSelection::PER_SHEET),
            'count' => $teachers->count(),
        ]);
    }
}
