<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-error text-on-error rounded-xl font-bold text-body-md shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-error focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>