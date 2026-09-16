<aside class="fixed left-0 top-0 h-screen w-sidebar bg-surface border-r border-outline-variant hidden md:flex flex-col z-30 overflow-y-auto">
    <div class="p-gutter py-6">
        <span class="font-headline-md text-headline-md font-bold text-primary">Dentamor</span>
    </div>

    <div class="px-4 py-2 flex items-center space-x-3 mb-6">
        <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-bold">
            {{ substr(Auth::user()->name ?? 'U', 0, 2) }}
        </div>
        <div class="flex flex-col overflow-hidden">
            <span class="font-label-md text-on-surface truncate">{{ Auth::user()->name ?? 'Usuario' }}</span>
            <span class="text-label-sm text-outline truncate uppercase tracking-wider">{{ Auth::user()->email ?? '' }}</span>
        </div>
    </div>

    <nav class="flex-1 px-2 space-y-1">
        <a href="{{ route('dashboard') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'active-sidebar-item' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined mr-3">dashboard</span>
            <span class="font-body-md">Dashboard</span>
        </a>

        <a href="{{ route('invoices.index') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('invoices.index') ? 'active-sidebar-item' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined mr-3">description</span>
            <span class="font-body-md">Comprobantes</span>
        </a>

        <a href="{{ route('invoices.create') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('invoices.create') ? 'active-sidebar-item' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined mr-3">add_circle</span>
            <span class="font-body-md">Nueva Factura</span>
        </a>

        <a href="{{ route('clients.index') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('clients.*') ? 'active-sidebar-item' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined mr-3">group</span>
            <span class="font-body-md">Clientes</span>
        </a>

        <a href="{{ route('services.index') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('services.*') ? 'active-sidebar-item' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined mr-3">inventory_2</span>
            <span class="font-body-md">Servicios</span>
        </a>

        <a href="{{ route('payments.index') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('payments.index') ? 'active-sidebar-item' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined mr-3">payments</span>
            <span class="font-body-md">Pagos</span>
        </a>

        <a href="{{ route('payments.bulk') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('payments.bulk') ? 'active-sidebar-item' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined mr-3">dynamic_feed</span>
            <span class="font-body-md">Facturar en Bloque</span>
        </a>

        <a href="{{ route('company.settings') }}"
           class="flex items-center px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('company.*') ? 'active-sidebar-item' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined mr-3">settings</span>
            <span class="font-body-md">Empresa</span>
        </a>
    </nav>

    <div class="p-4 border-t border-outline-variant mt-auto">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 text-error font-semibold hover:bg-error-container rounded-lg transition-colors">
                <span class="material-symbols-outlined">logout</span>
                <span class="font-label-md">Cerrar Sesión</span>
            </button>
        </form>
    </div>
</aside>

<header class="fixed top-0 left-0 md:left-sidebar right-0 h-16 bg-surface border-b border-outline-variant px-gutter flex items-center justify-between z-20">
    <div class="flex items-center">
        <button class="md:hidden mr-4 text-on-surface">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="hidden md:flex items-center gap-2">
            <span class="material-symbols-outlined text-secondary" title="Sistema Conectado a SUNAT">cloud_done</span>
            <span class="text-label-md text-secondary font-bold">SUNAT Conectado</span>
        </div>
    </div>
    <div class="flex items-center space-x-4">
        <button class="p-2 hover:bg-surface-container-high rounded-full relative text-on-surface-variant">
            <span class="material-symbols-outlined">notifications</span>
            <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
        </button>
        <div class="h-8 w-px bg-outline-variant"></div>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-primary text-xs font-bold">
                {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
            </div>
        </div>
    </div>
</header>

<nav class="fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 py-3 bg-surface border-t border-outline-variant shadow-2xl md:hidden">
    <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('dashboard') ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined {{ request()->routeIs('dashboard') ? 'filled' : '' }}">home</span>
        <span class="text-label-sm mt-1 font-semibold uppercase">Inicio</span>
    </a>
    <a href="{{ route('invoices.create') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('invoices.create') ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined" style="{{ request()->routeIs('invoices.create') ? 'font-variation-settings: FILL 1;' : '' }}">add_circle</span>
        <span class="text-label-sm mt-1 font-semibold uppercase">Facturar</span>
    </a>
    <a href="{{ route('invoices.index') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('invoices.*') ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined">history</span>
        <span class="text-label-sm mt-1 font-semibold uppercase">Docs</span>
    </a>
    <a href="{{ route('company.settings') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('company.*') ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined">more_horiz</span>
        <span class="text-label-sm mt-1 font-semibold uppercase">Más</span>
    </a>
</nav>
