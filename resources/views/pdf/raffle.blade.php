{{--
    The record in PDF, rendered by dompdf: CSS 2.1 with tables for layout, the
    DejaVu fonts it ships (they cover every accent) and the union's logo, read
    from disk (it has a white background, like the paper).
--}}
@php
    $several = $raffle->winners_count > 1;
    $winnerPositions = $raffle->winners->pluck('pivot.winner_position', 'id');

    $quorum = match (true) {
        $raffle->assembly === null => __('There was no assembly open'),
        $raffle->quorum_met === null => __('The assembly did not require quorum'),
        $raffle->quorum_met => __('Met when drawing'),
        default => __('Not met when drawing'),
    };

    $details = [
        __('Date and time') => Str::ucfirst($raffle->drawn_at->translatedFormat('l j \d\e F \d\e Y, g:i a')),
        __('Assembly') => $raffle->assembly
            ? $raffle->assembly->name.' · '.$raffle->assembly->date->translatedFormat('j \d\e F \d\e Y').($raffle->assembly->location ? ' · '.$raffle->assembly->location : '')
            : __('None open'),
        __('Quorum') => $quorum,
        __('Who took part') => $raffle->filter_description,
        __('Participants') => number_format($raffle->participants_count, 0, ',', '.'),
        $several ? __('Winners') : __('Winner') => $raffle->winners_count,
        __('Drawn by') => $raffle->drawer?->name ?? '—',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 32mm 18mm 22mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #1c2a24;
        }

        header {
            position: fixed;
            top: -22mm;
            left: 0;
            right: 0;
        }

        footer {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            border-top: 0.5pt solid #d5ddd8;
            padding-top: 2mm;
            font-size: 7.5pt;
            color: #6b7a73;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            vertical-align: top;
            text-align: left;
        }

        .brand td {
            vertical-align: middle;
        }

        .brand-name {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 1.5pt;
            text-transform: uppercase;
            color: #195840;
        }

        .brand-sub {
            font-size: 7.5pt;
            color: #6b7a73;
        }

        .record-number {
            text-align: right;
            font-size: 7.5pt;
            color: #6b7a73;
        }

        .record-number strong {
            display: block;
            font-family: 'DejaVu Serif', serif;
            font-size: 13pt;
            color: #1c2a24;
        }

        .rule {
            height: 1.5pt;
            margin: 2.5mm 0 0;
            background: #f2b72f;
        }

        h1 {
            margin: 0 0 1mm;
            font-family: 'DejaVu Serif', serif;
            font-size: 20pt;
            font-weight: bold;
            line-height: 1.15;
            color: #0f3a2b;
        }

        .lead {
            margin: 0 0 5mm;
            font-size: 10pt;
            color: #4a5a53;
        }

        h2 {
            margin: 7mm 0 2.5mm;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.2pt;
            text-transform: uppercase;
            color: #195840;
        }

        .details td {
            padding: 1.2mm 0;
            border-bottom: 0.5pt solid #e6ebe8;
        }

        .details td.term {
            width: 34%;
            color: #6b7a73;
        }

        .details td.value {
            font-weight: bold;
        }

        .warning {
            color: #b3372e;
        }

        .winners th,
        .participants th {
            padding: 1.5mm 2mm;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.6pt;
            text-transform: uppercase;
            color: #6b7a73;
            border-bottom: 0.75pt solid #c9d3ce;
        }

        .winners td {
            padding: 2mm;
            border-bottom: 0.5pt solid #e6ebe8;
        }

        .winners .position {
            width: 9mm;
            font-family: 'DejaVu Serif', serif;
            font-size: 12pt;
            font-weight: bold;
            color: #9c5211;
        }

        .winners .name {
            font-size: 11pt;
            font-weight: bold;
            color: #0f3a2b;
        }

        .forfeits .position,
        .forfeits .name {
            color: #6b7a73;
        }

        .muted {
            color: #6b7a73;
        }

        .code {
            white-space: nowrap;
            font-weight: bold;
            letter-spacing: 0.3pt;
        }

        .statement {
            margin-top: 5mm;
            padding: 3.5mm 4mm;
            border-left: 2pt solid #2f8963;
            background: #f1f7f4;
            font-size: 8.5pt;
            color: #33443c;
        }

        .signatures {
            margin-top: 14mm;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 33.33%;
            padding: 0 4mm;
        }

        .signature-line {
            border-top: 0.75pt solid #1c2a24;
            padding-top: 1.5mm;
            font-size: 8pt;
            font-weight: bold;
        }

        .signature-field {
            margin-top: 2.5mm;
            font-size: 7.5pt;
            color: #6b7a73;
        }

        .annex {
            page-break-before: always;
        }

        .participants td {
            padding: 1.1mm 2mm;
            font-size: 8pt;
            border-bottom: 0.5pt solid #eef1ef;
        }

        .participants tr.won td {
            background: #fdf4dc;
            font-weight: bold;
        }

        .participants .index {
            width: 10mm;
            color: #9aa7a1;
        }
    </style>
</head>
<body>
    <header>
        <table class="brand">
            <tr>
                <td style="width: 13mm">
                    <img src="{{ public_path('img/simac-logo.jpg') }}" width="42" height="36" alt="">
                </td>
                <td>
                    <div class="brand-name">{{ __('Casanare Teachers\' Union') }}</div>
                    <div class="brand-sub">SIMAC · {{ __('Assemblies and raffles') }}</div>
                </td>
                <td class="record-number">
                    {{ __('Raffle record') }}
                    <strong>{{ __('No. :number', ['number' => $raffle->id]) }}</strong>
                </td>
            </tr>
        </table>
        <div class="rule"></div>
    </header>

    {{-- The page number is written over each page once it is laid out (RaffleRecordController). --}}
    <footer>{{ $title }} · {{ __('Generated on :date', ['date' => now()->translatedFormat('j \d\e F \d\e Y, g:i a')]) }}</footer>

    <main>
        <h1>{{ __('Raffle record') }}</h1>
        <p class="lead">{{ $raffle->prize }}</p>

        <table class="details">
            @foreach ($details as $term => $detail)
                <tr>
                    <td class="term">{{ $term }}</td>
                    <td @class(['value', 'warning' => $term === __('Quorum') && $raffle->quorum_met === false])>{{ $detail }}</td>
                </tr>
            @endforeach
        </table>

        <h2>{{ $several ? __('Winners') : __('Winner') }}</h2>

        <table class="winners">
            <thead>
                <tr>
                    <th>{{ $several ? '#' : '' }}</th>
                    <th>{{ __('Teacher') }}</th>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('School') }}</th>
                    <th>{{ __('Municipality') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($raffle->winners as $winner)
                    <tr>
                        <td class="position">{{ $several ? $winner->pivot->winner_position : '★' }}</td>
                        <td class="name">{{ $winner->name }}</td>
                        <td class="code">{{ $winner->code }}</td>
                        <td>{{ $winner->school?->name ?? '—' }}</td>
                        <td>{{ $winner->city->name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($raffle->forfeits->isNotEmpty())
            <h2>{{ __('Did not come forward') }}</h2>

            <table class="winners forfeits">
                <thead>
                    <tr>
                        <th>{{ $several ? '#' : '' }}</th>
                        <th>{{ __('Teacher') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Municipality') }}</th>
                        <th>{{ __('Declared') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($raffle->forfeits as $absent)
                        <tr>
                            <td class="position">{{ $several ? $absent->pivot->forfeited_position : '—' }}</td>
                            <td class="name">{{ $absent->name }}</td>
                            <td class="code">{{ $absent->code }}</td>
                            <td>{{ $absent->city->name }}</td>
                            <td>
                                {{ $absent->pivot->forfeited_at->translatedFormat('g:i a') }}
                                @if ($declarer = $declarers[$absent->pivot->forfeited_by] ?? null)
                                    · {{ $declarer }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="statement">
            {{ __('The server chose the winners with the system\'s cryptographic generator, over the list of participants in the database. The record was saved before the animation: the screen only showed a result already sealed.') }}
            @if ($raffle->forfeits->isNotEmpty())
                {{ __('A winner who did not come forward was replaced the same way, among the participants still in the raffle, before the screen showed the replacement.') }}
            @endif
            {{ trans_choice('{1} The list of the :count participant is attached.|[2,*] The list of the :count participants is attached.', $raffle->participants_count) }}
        </div>

        <table class="signatures">
            <tr>
                @foreach ([__('Signature'), __('Signature'), __('Signature')] as $signature)
                    <td>
                        <div class="signature-line">{{ $signature }}</div>
                        <div class="signature-field">{{ __('Name') }}:</div>
                        <div class="signature-field">{{ __('Office held') }}:</div>
                    </td>
                @endforeach
            </tr>
        </table>

        <section class="annex">
            <h2 style="margin-top: 0">{{ __('Annex · Participants (:count)', ['count' => number_format($raffle->participants_count, 0, ',', '.')]) }}</h2>

            <table class="participants">
                <thead>
                    <tr>
                        <th class="index">#</th>
                        <th>{{ __('Teacher') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('School') }}</th>
                        <th>{{ __('Municipality') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($participants as $participant)
                        @php
                            $position = $winnerPositions[$participant->id] ?? null;
                        @endphp
                        <tr @class(['won' => $position !== null])>
                            <td class="index">{{ $loop->iteration }}</td>
                            <td>
                                {{ $participant->name }}
                                @if ($position !== null)
                                    · {{ $several ? __('Winner :position', ['position' => $position]) : __('Winner') }}
                                @elseif ($participant->pivot->forfeited_position !== null)
                                    · {{ __('Did not come forward') }}
                                @endif
                            </td>
                            <td class="code">{{ $participant->code }}</td>
                            <td>{{ $participant->school?->name ?? '—' }}</td>
                            <td>{{ $participant->city->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
