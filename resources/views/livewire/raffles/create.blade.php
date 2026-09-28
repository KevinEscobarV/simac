<div wire:poll.15s.visible>
    @php($projection = $this->projection)

    @if ($projection->isLive())
        <x-page-header :title="__('Raffle on screen')" :description="__('Drive the screens from here: nothing is projected on this page.')" />

        <div class="mx-auto max-w-3xl">
            <x-raffles.console :projection="$projection" :winners="$this->winners" :peeking="$peeking" :screens="$this->screens" />

            <p class="mt-4 px-1 text-center text-xs text-zinc-500 dark:text-zinc-400">
                <flux:link :href="route('screen')" target="_blank" class="font-semibold">{{ __('Open the projection screen') }}</flux:link>
                {{ __('on the projector and leave it there for the whole assembly.') }}
            </p>
        </div>

        <flux:modal name="projection-release" class="max-w-md">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Cancel the projection?') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('The screens go back to rest. The record is already saved and stays in the history.') }}</flux:text>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Keep projecting') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="release">{{ __('Cancel projection') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @else
        @php($assembly = $this->assembly)
        @php($count = $this->participantsCount)
        @php($winnersCount = ctype_digit($form->winners_count) ? (int) $form->winners_count : 1)

        <x-page-header :title="__('New raffle')" :description="__('Choose who takes part, what is raffled and how the screen presents it.')" />

        <div class="grid gap-5 lg:grid-cols-[1.4fr_1fr]">
            <div class="space-y-5">
                <x-raffles.step number="1" :title="__('Who takes part?')">
                    <div class="space-y-2.5">
                        <x-raffles.option
                            wire:model.live="form.present_only"
                            icon="clock"
                            :title="__('Only teachers who are present')"
                            :description="$assembly ? __('Uses the attendance of “:name”.', ['name' => $assembly->name]) : __('Needs an open assembly.')"
                            :disabled="$assembly === null"
                        />
                        <x-raffles.option
                            wire:model.live="form.union_members_only"
                            icon="shield-check"
                            :title="__('Only union members')"
                            :description="__('Leaves out the teachers who are not affiliated.')"
                        />
                        <x-raffles.option
                            wire:model.live="form.exclude_previous_winners"
                            icon="trophy"
                            :title="__('Without earlier winners')"
                            :description="$assembly ? __('Whoever already won at this assembly does not take part again.') : __('Applies while an assembly is open.')"
                        />
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <flux:select wire:model.live="form.city_id" :label="__('Municipality')">
                            <flux:select.option value="">{{ __('Every municipality') }}</flux:select.option>
                            @foreach ($this->cities as $city)
                                <flux:select.option :value="$city->id" wire:key="city-{{ $city->id }}">{{ $city->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="form.school_id" :label="__('School')" :disabled="$this->schools->isEmpty()">
                            <flux:select.option value="">{{ $form->city_id === '' ? __('Choose a municipality first') : __('Every school') }}</flux:select.option>
                            @foreach ($this->schools as $school)
                                <flux:select.option :value="$school->id" wire:key="school-{{ $school->id }}">{{ $school->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </x-raffles.step>

                <x-raffles.step number="2" :title="__('Prize and winners')">
                    <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
                        <flux:input wire:model="form.prize" :label="__('Prize')" :placeholder="__('e.g. 50-inch television')" autocomplete="off" />
                        <flux:input wire:model.live.debounce.400ms="form.winners_count" type="number" min="1" :max="App\Livewire\Forms\RaffleForm::MAX_WINNERS" :label="__('Winners')" />
                    </div>
                    <flux:text class="mt-3 text-xs">
                        {{ __('With several winners, each "Go!" reveals the next one and the screen ends with the roll of honor. Nobody can win twice in the same raffle.') }}
                    </flux:text>
                </x-raffles.step>

                <x-raffles.step number="3" :title="__('Animation')">
                    <div class="grid gap-2.5">
                        @foreach (App\Enums\RaffleAnimation::cases() as $animation)
                            <label wire:key="animation-{{ $animation->value }}" class="group flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-zinc-200 bg-white p-3.5 transition hover:border-zinc-300 has-checked:border-brand-500 has-checked:bg-brand-50 has-checked:shadow-card dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20 dark:has-checked:border-brand-500 dark:has-checked:bg-brand-500/10">
                                <input type="radio" wire:model.live="form.animation" value="{{ $animation->value }}" class="peer sr-only" />
                                <x-raffles.animation-illustration :animation="$animation" :size="52" />
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-bold text-zinc-900 dark:text-white">{{ $animation->label() }}</span>
                                    <span class="mt-0.5 block text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $animation->description() }}</span>
                                </span>
                                <span class="flex size-5 shrink-0 items-center justify-center rounded-full border-2 border-zinc-300 text-transparent transition peer-focus-visible:ring-2 peer-focus-visible:ring-accent peer-focus-visible:ring-offset-2 group-has-checked:border-brand-600 group-has-checked:bg-brand-600 group-has-checked:text-white dark:border-white/20">
                                    <flux:icon.check variant="micro" class="size-3" />
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @if ($form->animation === App\Enums\RaffleAnimation::Wheel->value && $count > App\Livewire\Raffles\Create::WHEEL_LIMIT)
                        <p class="mt-4 flex items-start gap-2 rounded-xl border border-gold-200 bg-gold-50 px-3.5 py-2.5 text-xs text-gold-800 dark:border-gold-400/30 dark:bg-gold-400/10 dark:text-gold-200">
                            <flux:icon.exclamation-triangle variant="micro" class="mt-px size-3.5 shrink-0" />
                            {{ __('With :count participants the wheel gets crowded. The name drum reads better with long lists.', ['count' => $count]) }}
                        </p>
                    @endif
                </x-raffles.step>
            </div>

            {{-- Summary --}}
            <aside class="lg:sticky lg:top-6 lg:self-start">
                <div class="dark overflow-hidden rounded-2xl border border-brand-800 bg-institutional p-6 shadow-raised">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-[0.68rem] font-semibold tracking-[0.2em] text-gold-300/80 uppercase">{{ __('Raffle summary') }}</div>
                        <x-raffles.screens :count="$this->screens" />
                    </div>

                    <div class="mt-5 flex items-baseline gap-3">
                        <span class="font-display text-6xl leading-none font-semibold text-white tabular-nums">{{ number_format($count, 0, ',', '.') }}</span>
                        <span class="text-sm text-white/55">{{ trans_choice('{0} participants|{1} participant|[2,*] participants', $count) }}</span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1.5 text-[0.7rem] font-medium">
                        <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/75">{{ $this->filterDescription }}</span>
                        <span class="rounded-md border border-white/10 bg-white/8 px-2 py-1 text-white/75">{{ trans_choice('{1} :count winner|[2,*] :count winners', $winnersCount) }}</span>
                        @if (filled($form->prize))
                            <span class="inline-flex items-center gap-1 rounded-md border border-gold-400/30 bg-gold-400/10 px-2 py-1 text-gold-200">
                                <flux:icon.gift variant="micro" class="size-3.5" />
                                {{ Str::limit(Str::squish($form->prize), 40) }}
                            </span>
                        @endif
                    </div>

                    @if ($count > 0)
                        <div class="mt-5 flex max-h-48 flex-wrap gap-1.5 overflow-y-auto border-t border-white/10 pt-5 pe-1">
                            @foreach ($this->participantSample as $teacher)
                                <span wire:key="participant-{{ $teacher->id }}" class="rounded-md bg-white/6 px-2 py-1 text-[0.7rem] text-white/60">{{ $teacher->short_name }}</span>
                            @endforeach
                            @if ($count > App\Livewire\Raffles\Create::SAMPLE)
                                <span class="rounded-md px-2 py-1 text-[0.7rem] text-white/40">{{ __('and :count more', ['count' => number_format($count - App\Livewire\Raffles\Create::SAMPLE, 0, ',', '.')]) }}</span>
                            @endif
                        </div>
                    @endif

                    {{-- Drawing without quorum is the decision of whoever chairs the assembly, but not one to take without seeing it. --}}
                    @if ($this->quorum && ! $this->quorum->isMet())
                        <div class="mt-6 flex items-start gap-2.5 rounded-xl border border-gold-400/35 bg-gold-400/10 px-3.5 py-3 text-xs text-gold-100">
                            <flux:icon.exclamation-triangle variant="micro" class="mt-px size-4 shrink-0 text-gold-300" />
                            <span>
                                <strong class="font-semibold">{{ __('The assembly does not have quorum.') }}</strong>
                                {{ __(':present of the :required union members required are present. You can draw anyway, but the record will say so.', ['present' => $this->quorum->presentMembers, 'required' => $this->quorum->required()]) }}
                            </span>
                        </div>
                    @endif

                    <x-gold-button icon="tv" wire:click="draw" wire:loading.attr="disabled" wire:target="draw" :disabled="$count < 2 || $count <= $winnersCount" class="mt-6 w-full">
                        <span wire:loading.remove wire:target="draw">{{ __('Load on the screen') }}</span>
                        <span wire:loading wire:target="draw">{{ __('Drawing…') }}</span>
                    </x-gold-button>

                    <div class="mt-3 space-y-2 text-xs">
                        @foreach (['prize', 'winners_count', 'animation', 'city_id', 'school_id', 'draw'] as $field)
                            @error('form.'.$field)
                                <p class="flex items-start gap-2 text-red-300">
                                    <flux:icon.exclamation-circle variant="micro" class="mt-px size-3.5 shrink-0" />
                                    {{ $message }}
                                </p>
                            @enderror
                        @endforeach

                        @if ($count < 2)
                            <p class="flex items-start gap-2 text-gold-200/90">
                                <flux:icon.exclamation-triangle variant="micro" class="mt-px size-3.5 shrink-0" />
                                {{ $form->present_only ? __('At least 2 teachers who are present and meet the filters are needed. Check the attendance.') : __('At least 2 participants are needed. Adjust the filters.') }}
                            </p>
                        @elseif ($count <= $winnersCount)
                            <p class="flex items-start gap-2 text-gold-200/90">
                                <flux:icon.exclamation-triangle variant="micro" class="mt-px size-3.5 shrink-0" />
                                {{ __('There are :participants participants for :winners winners: there must be more participants than winners.', ['participants' => $count, 'winners' => $winnersCount]) }}
                            </p>
                        @endif

                        @if ($this->screens === 0)
                            <p class="flex items-start gap-2 text-gold-200/90">
                                <flux:icon.tv variant="micro" class="mt-px size-3.5 shrink-0" />
                                <span>
                                    {{ __('No screen is connected.') }}
                                    <a href="{{ route('screen') }}" target="_blank" class="font-semibold text-gold-200 underline underline-offset-4">{{ __('Open the projection screen') }}</a>
                                    {{ __('on the projector before drawing.') }}
                                </span>
                            </p>
                        @endif
                    </div>

                    <p class="mt-5 flex items-start gap-2 text-[0.7rem] leading-relaxed text-white/45">
                        <flux:icon.shield-check variant="micro" class="mt-px size-3.5 shrink-0" />
                        {{ __('The server picks the winners with a cryptographic generator and seals the record before the animation starts. The screen announces the raffle and waits for your order.') }}
                    </p>
                </div>
            </aside>
        </div>
    @endif
</div>
