@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-semibold text-body-sm text-secondary']) }}>
        {{ $status }}
    </div>
@endif