@props([
    'options' => [],
    'selected' => '',
    'wireSetKey' => null,
    'placeholder' => 'Seleccionar...',
    'searchable' => false,
    'clearable' => false,
    'large' => false,
    'buttonClass' => '',
])

@php
    $paddings = $large ? 'px-4 py-3' : 'px-3 py-2.5';
    $current = collect($options)->first(fn($o) => (string) ($o['id'] ?? '') === (string) $selected);
@endphp

<div
    class="relative"
    x-data="{ open: false, query: '', positionPanel() { const t = this.$refs.trigger.getBoundingClientRect(); const p = this.$refs.panel; const spaceBelow = window.innerHeight - t.bottom - 8; const spaceAbove = t.top - 8; p.style.position = 'fixed'; p.style.left = t.left + 'px'; p.style.width = t.width + 'px'; p.style.top = 'auto'; p.style.bottom = 'auto'; p.style.maxHeight = 'none'; p.style.top = (t.bottom + 4) + 'px'; const natural = p.offsetHeight; if (spaceBelow >= natural || spaceBelow >= spaceAbove) { p.style.maxHeight = Math.max(96, spaceBelow) + 'px'; p.style.top = (t.bottom + 4) + 'px'; } else { p.style.maxHeight = Math.max(96, spaceAbove) + 'px'; p.style.bottom = (window.innerHeight - t.top + 4) + 'px'; } } }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @scroll.window="open = false"
    @resize.window="open = false"
>
    <button type="button"
            x-ref="trigger"
            x-on:click="open = !open; if (open) positionPanel()"
            :aria-expanded="open"
            class="w-full {{ $paddings }} {{ trim($buttonClass) }} bg-surface-container-low border-none rounded-xl text-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none text-left cursor-pointer flex items-center justify-between gap-2 transition-all">
        <span class="truncate {{ $current ? 'text-on-surface' : 'text-outline' }}">
            @if($current)
                @if(!empty($current['code'])) {{ $current['code'] }} - @endif{{ $current['label'] }}
            @else
                {{ $placeholder }}
            @endif
        </span>
        <span class="material-symbols-outlined text-on-surface-variant text-[18px] shrink-0 transition-transform duration-200"
              :class="{ 'rotate-180': open }">expand_more</span>
    </button>

    <div x-cloak
         x-ref="panel"
         x-show="open"
         x-transition:enter="transition ease-out duration-100 origin-top"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="z-50 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg overflow-hidden">
        @if($searchable)
            <input x-model="query"
                   type="text"
                   placeholder="Buscar..."
                   class="w-full bg-surface-container-low border-b border-outline-variant px-3 py-2 text-body-md text-on-surface placeholder:text-outline outline-none focus:ring-0">
        @endif

        <div class="max-h-56 overflow-y-auto custom-scrollbar p-1">
            @if($clearable)
                <button type="button"
                        wire:key="bs-clear-{{ $wireSetKey }}"
                        x-show="!query"
                        x-on:click="open = false; query = ''; Livewire.first().set(@js($wireSetKey), '')"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-left transition-colors hover:bg-surface-container-low">
                    <span class="text-body-md text-outline">— Sin seleccionar —</span>
                </button>
            @endif

            @forelse($options as $option)
                @php
                    $isSelected = $current && (string) ($option['id'] ?? '') === (string) $selected;
                    $needle = strtolower(trim(($option['label'] ?? '') . ' ' . ($option['code'] ?? '')));
                @endphp
                <button type="button"
                        wire:key="bs-{{ $wireSetKey }}-{{ $option['id'] ?? '' }}"
                        x-on:click="open = false; query = ''; Livewire.first().set(@js($wireSetKey), @js((string) ($option['id'] ?? '')))"
                        @if($searchable)
                        x-show="query.trim() === '' || {{ \Illuminate\Support\Js::from($needle)->toHtml() }}.indexOf(query.toLowerCase().trim()) !== -1"
                        @endif
                        class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-left transition-colors {{ $isSelected ? 'bg-primary-fixed text-on-primary-fixed' : 'text-on-surface hover:bg-primary-fixed/60' }}">
                    <span class="flex items-center gap-2 min-w-0">
                        @if(!empty($option['code']))
                            <span class="font-mono text-mono-md text-on-surface-variant shrink-0">{{ $option['code'] }}</span>
                        @endif
                        <span class="truncate text-body-md">{{ $option['label'] }}</span>
                    </span>
                    <span class="material-symbols-outlined text-[18px] shrink-0 text-primary-container" x-show="{{ $isSelected ? 'true' : 'false' }}">check</span>
                </button>
            @empty
                <div class="px-3 py-2 text-body-sm text-outline">Sin opciones</div>
            @endforelse
        </div>
    </div>
</div>