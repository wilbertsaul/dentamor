@props([
    'size' => 44,
    'showText' => true,
    'light' => false,
    'href' => null,
    'subtitle' => 'Odontología con Amor',
    'tag' => 'a',
])

@php
    $tag = $href ? 'a' : 'div';
    $wordmarkDark = $light ? 'text-white' : 'text-primary-dark';
    $wordmarkAccent = $light ? 'text-white' : 'text-primary';
    $subtitleColor = $light ? 'text-white/70' : 'text-secondary';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 group transition-transform active:scale-95']) }}>
    <svg class="shrink-0 drop-shadow-sm" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 74 70" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Dentamor">
        <g transform="translate(3, 2)">
            <path d="M32 58C32 58 6 42 6 22C6 11 15 3 26 3C30.5 3 34.5 5.5 37 9.5C39.5 5.5 43.5 3 48 3C59 3 68 11 68 22C68 42 42 58 42 58L37 63L32 58Z" fill="#00C4A7"/>
            <path d="M25 18C23 18 20 20 19 23C18 27 19 32 20 37C21 42 22 47 24 50C25.5 52 28 52 29 48C30.5 42 32 37 34 37C36 37 37.5 42 39 48C40 52 42.5 52 44 50C46 47 47 42 48 37C49 32 50 27 49 23C48 20 45 18 43 18C39 18 37 21 34 21C31 21 29 18 25 18Z" fill="#FFFFFF"/>
            <ellipse cx="28" cy="27" fill="#1E293B" rx="2" ry="2.5"/>
            <ellipse cx="40" cy="27" fill="#1E293B" rx="2" ry="2.5"/>
            <path d="M30 32C32 35.5 36 35.5 38 32" stroke="#1E293B" stroke-linecap="round" stroke-width="1.8" fill="none"/>
            <circle cx="25" cy="30" fill="#FF8A80" opacity="0.6" r="2"/>
            <circle cx="43" cy="30" fill="#FF8A80" opacity="0.6" r="2"/>
        </g>
    </svg>

    @if($showText)
        <div class="flex flex-col">
            <div class="leading-none text-[22px] font-extrabold tracking-tight font-headline-md">
                <span class="{{ $wordmarkDark }}">Dent</span><span class="{{ $wordmarkAccent }}">amor</span>
            </div>
            <span class="text-[8.5px] font-bold {{ $subtitleColor }} tracking-wider mt-1 uppercase font-body-md">{{ $subtitle }}</span>
        </div>
    @endif
</{{ $tag }}>
