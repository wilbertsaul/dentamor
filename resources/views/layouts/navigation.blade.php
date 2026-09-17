<aside class="fixed left-0 top-0 h-screen hidden md:flex flex-col bg-white border-r border-outline-variant w-sidebar z-40 shadow-brand-soft">
    <div class="px-5 pt-6 pb-5 border-b border-outline-variant/60 flex items-center">
        <x-logo :size="44" href="{{ route('dashboard') }}" subtitle="Odontología con Amor" />
    </div>

    <div class="px-4 py-4">
        <div class="flex items-center gap-3 p-2.5 bg-surface-container-low border border-outline-variant/80 rounded-xl">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-primary to-secondary flex items-center justify-center text-white font-bold text-xs shadow-sm ring-2 ring-primary/20">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
            </div>
            <div class="overflow-hidden">
                <p class="font-semibold text-xs text-on-surface truncate">{{ Auth::user()->name ?? 'Usuario' }}</p>
                <p class="text-[10px] text-on-surface-variant truncate font-mono-md">{{ Auth::user()->email ?? '' }}</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-3 space-y-1 overflow-y-auto custom-scrollbar">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border-l-4 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-primary-container text-primary-dark font-bold border-primary' : 'text-on-surface-variant hover:text-primary-dark hover:bg-surface-container-low font-medium border-transparent' }}">
            <span class="material-symbols-outlined text-[21px] {{ request()->routeIs('dashboard') ? 'text-primary' : '' }}">dashboard</span>
            <span class="text-sm">Dashboard</span>
        </a>

        <a href="{{ route('invoices.index') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border-l-4 transition-all duration-200 {{ request()->routeIs('invoices.index') ? 'bg-primary-container text-primary-dark font-bold border-primary' : 'text-on-surface-variant hover:text-primary-dark hover:bg-surface-container-low font-medium border-transparent' }}">
            <span class="material-symbols-outlined text-[21px] {{ request()->routeIs('invoices.index') ? 'text-primary' : '' }}">receipt_long</span>
            <span class="text-sm">Comprobantes</span>
        </a>

        <a href="{{ route('invoices.create') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border-l-4 transition-all duration-200 {{ request()->routeIs('invoices.create') ? 'bg-primary-container text-primary-dark font-bold border-primary' : 'text-on-surface-variant hover:text-primary-dark hover:bg-surface-container-low font-medium border-transparent' }}">
            <span class="material-symbols-outlined text-[21px] {{ request()->routeIs('invoices.create') ? 'text-primary' : '' }}">add_circle_outline</span>
            <span class="text-sm">Nueva Factura</span>
        </a>

        <a href="{{ route('clients.index') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border-l-4 transition-all duration-200 {{ request()->routeIs('clients.*') ? 'bg-primary-container text-primary-dark font-bold border-primary' : 'text-on-surface-variant hover:text-primary-dark hover:bg-surface-container-low font-medium border-transparent' }}">
            <span class="material-symbols-outlined text-[21px] {{ request()->routeIs('clients.*') ? 'text-primary' : '' }}">group</span>
            <span class="text-sm">Pacientes / Clientes</span>
        </a>

        <a href="{{ route('services.index') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border-l-4 transition-all duration-200 {{ request()->routeIs('services.*') ? 'bg-primary-container text-primary-dark font-bold border-primary' : 'text-on-surface-variant hover:text-primary-dark hover:bg-surface-container-low font-medium border-transparent' }}">
            <span class="material-symbols-outlined text-[21px] {{ request()->routeIs('services.*') ? 'text-primary' : '' }}">medical_services</span>
            <span class="text-sm">Servicios Dentales</span>
        </a>

        <a href="{{ route('payments.index') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border-l-4 transition-all duration-200 {{ request()->routeIs('payments.index') ? 'bg-primary-container text-primary-dark font-bold border-primary' : 'text-on-surface-variant hover:text-primary-dark hover:bg-surface-container-low font-medium border-transparent' }}">
            <span class="material-symbols-outlined text-[21px] {{ request()->routeIs('payments.index') ? 'text-primary' : '' }}">payments</span>
            <span class="text-sm">Pagos</span>
        </a>

        <a href="{{ route('payments.bulk') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border-l-4 transition-all duration-200 {{ request()->routeIs('payments.bulk') ? 'bg-primary-container text-primary-dark font-bold border-primary' : 'text-on-surface-variant hover:text-primary-dark hover:bg-surface-container-low font-medium border-transparent' }}">
            <span class="material-symbols-outlined text-[21px] {{ request()->routeIs('payments.bulk') ? 'text-primary' : '' }}">layers</span>
            <span class="text-sm">Facturar en Bloque</span>
        </a>

        <a href="{{ route('company.settings') }}"
           class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg border-l-4 transition-all duration-200 {{ request()->routeIs('company.*') ? 'bg-primary-container text-primary-dark font-bold border-primary' : 'text-on-surface-variant hover:text-primary-dark hover:bg-surface-container-low font-medium border-transparent' }}">
            <span class="material-symbols-outlined text-[21px] {{ request()->routeIs('company.*') ? 'text-primary' : '' }}">domain</span>
            <span class="text-sm">Empresa / Clínica</span>
        </a>
    </nav>

    <div class="p-3 border-t border-outline-variant">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-3 px-3.5 py-2.5 w-full text-error hover:bg-error-container/40 rounded-lg text-sm font-semibold transition-colors">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span>Cerrar Sesión</span>
            </button>
        </form>
    </div>
</aside>

<header class="fixed top-0 left-0 md:left-sidebar right-0 h-16 bg-white border-b border-outline-variant px-6 lg:px-8 flex items-center justify-between z-30">
    <div class="flex items-center gap-4">
        <button class="md:hidden p-2 hover:bg-surface-container rounded-lg text-on-surface">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="hidden md:flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-3 py-1 rounded-full text-xs font-semibold">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="material-symbols-outlined text-[15px]">cloud_done</span>
            <span>SUNAT Conectado</span>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('invoices.create') }}"
           class="hidden sm:inline-flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-xl text-sm font-bold shadow-sm shadow-primary/30 transition-all active:scale-95">
            <span class="material-symbols-outlined text-lg filled">add_circle</span>
            <span>Nuevo Comprobante</span>
        </a>

        <div class="h-6 w-px bg-outline-variant mx-1 hidden sm:block"></div>

        <button class="p-2 text-on-surface-variant hover:text-primary-dark hover:bg-surface-container rounded-xl transition-colors relative" title="Notificaciones">
            <span class="material-symbols-outlined text-[22px]">notifications</span>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-error rounded-full ring-2 ring-white"></span>
        </button>

        <a href="{{ route('profile.edit') }}" class="w-8 h-8 rounded-full bg-primary-container text-primary-dark flex items-center justify-center font-bold text-xs ring-1 ring-primary/30">
            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
        </a>
    </div>
</header>

<nav class="fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-2 py-2 md:hidden bg-white border-t border-outline-variant shadow-lg">
    <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('dashboard') ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('dashboard') ? 'filled' : '' }}">dashboard</span>
        <span class="text-[9px] uppercase tracking-tighter mt-0.5">Inicio</span>
    </a>
    <a href="{{ route('invoices.create') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('invoices.create') ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('invoices.create') ? 'filled' : '' }}">add_circle</span>
        <span class="text-[9px] uppercase tracking-tighter mt-0.5">Emitir</span>
    </a>
    <a href="{{ route('invoices.index') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('invoices.*') ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('invoices.*') ? 'filled' : '' }}">receipt_long</span>
        <span class="text-[9px] uppercase tracking-tighter mt-0.5">Boletas</span>
    </a>
    <a href="{{ route('company.settings') }}" class="flex flex-col items-center justify-center {{ request()->routeIs('company.*') ? 'text-primary font-bold' : 'text-on-surface-variant' }}">
        <span class="material-symbols-outlined text-[20px]">more_horiz</span>
        <span class="text-[9px] uppercase tracking-tighter mt-0.5">Más</span>
    </a>
</nav>
