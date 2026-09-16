<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-5 py-3 bg-white border-2 border-outline-variant rounded-xl font-semibold text-body-md text-on-surface-variant hover:bg-surface-container-low focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>