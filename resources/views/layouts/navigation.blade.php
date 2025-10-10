{{-- resources/views/layouts/navigation.blade.php --}}
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Menú principal (desktop) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <div class="flex">
                {{-- Logo: al dashboard (si no hay sesión, te redirige a login por middleware) --}}
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                {{-- Links de navegación (ejemplo: Dashboard) --}}
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            {{-- Lado derecho del navbar --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                {{-- 🔒 Usuario AUTENTICADO: dropdown con Perfil / (opcional) Crear usuario / Logout --}}
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                              clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            {{-- Perfil (solo si existe la ruta) --}}
                            @if (Route::has('profile.edit'))
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>
                            @endif

                            {{-- 👑 Crear usuario (REGISTRO) SOLO visible para administradores
                                 - La ruta 'register' está protegida por ['auth','admin'] en routes/auth.php
                                 - Este link sólo aparece si el usuario tiene is_admin=true --}}
                            @if (auth()->user()?->is_admin && Route::has('register'))
                                <x-dropdown-link :href="route('register')">
                                    {{ __('Crear usuario') }}
                                </x-dropdown-link>
                            @endif

                            {{-- Logout: botón dentro de form POST --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth

                {{-- 🚪 Invitado (GUEST): SOLO mostrar "Login"
                     (El enlace "Register" se oculta para que el registro quede únicamente para admin) --}}
                @guest
                    <div class="flex items-center gap-4">
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:underline">
                                {{ __('Log in') }}
                            </a>
                        @endif
                        {{-- SIN enlace a Register para invitados --}}
                    </div>
                @endguest
            </div>

            {{-- Botón hamburguesa (móvil) --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menú responsive (móvil) --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        {{-- Links de navegación principales (móvil) --}}
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        {{-- Opciones según estado de autenticación (móvil) --}}
        @auth
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    @if (Route::has('profile.edit'))
                        <x-responsive-nav-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-responsive-nav-link>
                    @endif

                    {{-- 👑 Crear usuario SOLO para admin (móvil) --}}
                    @if (auth()->user()?->is_admin && Route::has('register'))
                        <x-responsive-nav-link :href="route('register')">
                            {{ __('Crear usuario') }}
                        </x-responsive-nav-link>
                    @endif

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth

        @guest
            {{-- Invitado (móvil): SOLO Login, sin Register --}}
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="mt-3 space-y-1 px-4">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="block py-2 text-gray-700 hover:underline">
                            {{ __('Log in') }}
                        </a>
                    @endif
                    {{-- SIN enlace a Register para invitados --}}
                </div>
            </div>
        @endguest
    </div>
</nav>
