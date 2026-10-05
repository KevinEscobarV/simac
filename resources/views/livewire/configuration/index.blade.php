@php
    $settings = $this->settings;
    $newImage = $form->image && ! $errors->has('form.image') ? $form->image->temporaryUrl() : null;
    $previewImage = $newImage ?? $settings->eventImageUrl();
@endphp

<div>
    <x-page-header :title="__('Configuration')" :description="__('How SIMAC presents itself to everyone who uses it.')" />

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        <section class="rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/10 dark:bg-zinc-900">
            <header class="border-b border-zinc-200/80 px-6 py-5 dark:border-white/10">
                <flux:heading size="lg">{{ __('Event') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('The event the union is holding. It is shown on the sign-in page, the home page and the projection screens, for every assembly until you change it.') }}
                </flux:text>
            </header>

            <form wire:submit="save" class="space-y-6 px-6 py-6">
                <flux:input wire:model.live.debounce.400ms="form.title" :label="__('Title')" :placeholder="__('E.g.: Teachers\' Sports Games :year', ['year' => now()->year])" maxlength="80" />

                <flux:input wire:model.live.debounce.400ms="form.subtitle" :label="__('Subtitle')" :description:trailing="__('Optional. For example, the place and the dates.')" maxlength="120" />

                <flux:field>
                    <flux:label>{{ __('Image') }}</flux:label>
                    <flux:description>{{ __('PNG with a transparent background looks best on the dark backgrounds. PNG, JPG or WebP, up to 5 MB.') }}</flux:description>

                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-zinc-200 bg-institutional p-2 dark:border-white/10">
                            @if ($previewImage)
                                <img src="{{ $previewImage }}" alt="{{ __('Event image') }}" class="max-h-full max-w-full object-contain">
                            @else
                                <flux:icon.photo variant="outline" class="size-8 text-white/30" />
                            @endif
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col items-start gap-2">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3.5 py-2 text-sm font-medium text-zinc-800 shadow-xs transition focus-within:ring-2 focus-within:ring-accent hover:bg-zinc-50 dark:border-white/15 dark:bg-white/5 dark:text-zinc-100 dark:hover:bg-white/10">
                                <flux:icon.arrow-up-tray variant="micro" class="size-4" />
                                {{ $previewImage ? __('Choose another image') : __('Choose an image') }}
                                <input type="file" wire:model="form.image" accept="image/png,image/jpeg,image/webp" class="sr-only">
                            </label>

                            <div wire:loading wire:target="form.image" class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Uploading…') }}</div>

                            @if ($newImage)
                                <flux:text class="text-xs">{{ __('New image: it is used once you save.') }}</flux:text>
                            @elseif ($settings->event_image_path)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeImage" wire:confirm="{{ __('Remove the event image? Nobody will see it any more.') }}">
                                    {{ __('Remove image') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <flux:error name="form.image" />
                </flux:field>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save, form.image">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </form>
        </section>

        <section class="space-y-3">
            <flux:text class="px-1 text-xs font-semibold tracking-[0.14em] uppercase">{{ __('Preview of the home page') }}</flux:text>

            @if (filled($form->title) || filled($form->subtitle) || $previewImage)
                <x-event.banner :title="$form->title" :subtitle="$form->subtitle" :image="$previewImage" />
            @else
                <div class="rounded-2xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-white/15 dark:text-zinc-400">
                    {{ __('With no title and no image, SIMAC shows only its own name.') }}
                </div>
            @endif
        </section>
    </div>
</div>
