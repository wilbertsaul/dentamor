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
    <img src="{{ asset('images/logo-app.png') }}" alt="Dentamor"
         class="shrink-0 drop-shadow-sm"
         style="height: {{ $size }}px; width: auto;"
         role="img" aria-label="Dentamor">

    @if($showText)
        <div class="flex flex-col">
            <div class="leading-none text-[22px] font-extrabold tracking-tight font-headline-md">
                <span class="{{ $wordmarkDark }}">Dent</span><span class="{{ $wordmarkAccent }}">amor</span>
            </div>
            <span class="text-[8.5px] font-bold {{ $subtitleColor }} tracking-wider mt-1 uppercase font-body-md">{{ $subtitle }}</span>
        </div>
    @endif
</{{ $tag }}>
