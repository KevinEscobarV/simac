<div x-data="confetti" aria-hidden="true" {{ $attributes->class('pointer-events-none fixed inset-0 z-30 overflow-hidden') }}>
    <template x-for="(piece, index) in pieces" :key="index">
        <div class="absolute top-0" :style="piece.fall">
            <div :style="piece.piece"></div>
        </div>
    </template>
</div>
