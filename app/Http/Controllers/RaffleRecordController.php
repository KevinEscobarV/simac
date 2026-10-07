<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as Document;
use Dompdf\Canvas;
use Dompdf\FontMetrics;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class RaffleRecordController extends Controller
{
    /** The right margin of the page (18 mm, as in the view), in points. */
    private const float RIGHT_MARGIN = 51.02;

    /** Where the footer's text starts, from the top of a letter page, in points. */
    private const float FOOTER_TOP = 757.5;

    /**
     * The record in PDF, to print, sign and file: the raffle, its winners
     * and every participant.
     */
    public function __invoke(Raffle $raffle): Response
    {
        $raffle->load(['assembly', 'drawer', 'winners.school', 'winners.city', 'forfeits.school', 'forfeits.city']);

        $participants = $raffle->participants()->with(['school', 'city'])->orderByName()->get();

        $document = Pdf::loadView('pdf.raffle', [
            'raffle' => $raffle,
            'participants' => $participants,
            'declarers' => $raffle->forfeitDeclarers(),
            'title' => __('Raffle record No. :number', ['number' => $raffle->id]),
        ])
            ->setPaper('letter');

        $this->numberPages($document);

        return $document->download(Str::slug(__('Raffle record')).'-'.$raffle->id.'.pdf');
    }

    /**
     * "Page 2 of 5" at the foot of every page. The total is only known once
     * everything is laid out, so it is written over the rendered pages.
     */
    private function numberPages(Document $document): void
    {
        $document->render();

        $document->getDomPDF()->getCanvas()->page_script(function (int $page, int $pages, Canvas $canvas, FontMetrics $fontMetrics): void {
            $text = __('Page :page of :pages', ['page' => $page, 'pages' => $pages]);
            $font = $fontMetrics->getFont('DejaVu Sans');
            $size = 7.5;

            $canvas->text(
                $canvas->get_width() - self::RIGHT_MARGIN - $fontMetrics->getTextWidth($text, $font, $size),
                self::FOOTER_TOP,
                $text,
                $font,
                $size,
                [0.42, 0.478, 0.451],
            );
        });
    }
}
