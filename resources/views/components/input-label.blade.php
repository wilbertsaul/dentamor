@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-label-md text-on-surface-variant mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>