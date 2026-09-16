@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl px-4 py-3 bg-surface-container-low border border-transparent text-body-md text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary focus:bg-surface outline-none transition-all']) }}>