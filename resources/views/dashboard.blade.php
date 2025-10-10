{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
    {{-- ============================
         Encabezado (Breeze + Tailwind)
       ============================ --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    {{-- ============================
         Contenedor principal
       ============================ --}}
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Mensaje flash (por ejemplo al guardar una venta) --}}
            @if (session('ok'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                    {{ session('ok') }}
                </div>
            @endif

            {{-- ============================
                 KPIs (indicadores rápidos)
                 * Los valores se pintan desde resources/js/dashboard.js
               ============================ --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="text-gray-500">Ventas hoy</div>
                    <div id="kpiVentasHoy" class="text-3xl font-bold">$0.00</div>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="text-gray-500">Órdenes</div>
                    <div id="kpiOrdenes" class="text-3xl font-bold">0</div>
                </div>
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="text-gray-500">Ticket promedio</div>
                    <div id="kpiAvg" class="text-3xl font-bold">$0.00</div>
                </div>
            </div>

            {{-- ============================
                 Filtros
                 IDs usados por dashboard.js:
                 - #start, #end, #category_id, #region_id
                 - #amount_min, #amount_max (nuevos)
                 - #btn-aplicar, #btn-csv
               ============================ --}}
            <div class="rounded-xl border bg-white p-5 shadow-sm mb-6">
                {{-- grid responsiva: en desktop 6 columnas, en mobile se apila --}}
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">

                    {{-- Fechas --}}
                    <div>
                        <label for="start" class="block text-sm text-gray-600 mb-1">Desde</label>
                        <input type="date" id="start" class="rounded-md border-gray-300 w-full" />
                    </div>

                    <div>
                        <label for="end" class="block text-sm text-gray-600 mb-1">Hasta</label>
                        <input type="date" id="end" class="rounded-md border-gray-300 w-full" />
                    </div>

                    {{-- Categoría
                       Si el controlador envía $categorias, se pintan aquí;
                       si no, el JS las cargará vía /api/filters/categories --}}
                    <div>
                        <label for="category_id" class="block text-sm text-gray-600 mb-1">Categoría</label>
                        <select id="category_id" class="rounded-md border-gray-300 w-full">
                            <option value="">Todas</option>
                            @isset($categorias)
                                @foreach($categorias as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    {{-- Región (mismo criterio que categoría) --}}
                    <div>
                        <label for="region_id" class="block text-sm text-gray-600 mb-1">Región</label>
                        <select id="region_id" class="rounded-md border-gray-300 w-full">
                            <option value="">Todas</option>
                            @isset($regiones)
                                @foreach($regiones as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>

                    {{-- NUEVOS: filtros por monto mínimo / máximo
                       El JS los envía como amount_min y amount_max --}}
                    <div>
                        <label for="amount_min" class="block text-sm text-gray-600 mb-1">Monto mín.</label>
                        <input type="number" step="0.01" id="amount_min" class="rounded-md border-gray-300 w-full" />
                    </div>

                    <div>
                        <label for="amount_max" class="block text-sm text-gray-600 mb-1">Monto máx.</label>
                        <input type="number" step="0.01" id="amount_max" class="rounded-md border-gray-300 w-full" />
                    </div>

                    {{-- Botones (abajo en mobile, a la derecha en desktop) --}}
                    <div class="md:col-span-6 flex flex-wrap gap-3">
                        <button id="btn-aplicar"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                            Aplicar
                        </button>

                        <a id="btn-csv" href="#"
                           class="inline-flex items-center rounded-md border px-4 py-2 text-indigo-700 border-indigo-200 hover:bg-indigo-50">
                            Exportar CSV
                        </a>

                        {{-- Acceso rápido para crear una venta real (opcional) --}}
                        @auth
                            <a href="{{ route('sales.create') }}"
                               class="inline-flex items-center rounded-md border px-4 py-2 text-gray-700 border-gray-200 hover:bg-gray-50">
                                Nueva venta
                            </a>
                        @endauth
                    </div>
                </div>
            </div>

            {{-- ============================
                 Gráficas (fila superior)
                 - #chart:  barras ↔ línea (auto) ventas por mes
                 - #pieChart: pie participación por categoría
               ============================ --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <h3 class="mb-3 text-gray-700 font-medium">Ventas por mes</h3>
                    <canvas id="chart" height="160"></canvas>
                    {{-- Nota: el "motor" en dashboard.js cambia a línea si hay ≥6 puntos --}}
                </div>

                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <h3 class="mb-3 text-gray-700 font-medium">Participación por categoría</h3>
                    <canvas id="pieChart" height="160"></canvas>
                    {{-- Cada categoría tiene color distinto (ver paleta en dashboard.js) --}}
                </div>
            </div>

            {{-- ============================
                 (NUEVA) Gráfica de PUNTOS (debajo)
                 Muestra la MISMA información que el pie (participación por categoría),
                 pero como puntos categóricos para lectura rápida.
                 ID: #scatterChart (lo usa dashboard.js)
               ============================ --}}
            <div class="mt-6">
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <h3 class="mb-3 text-gray-700 font-medium">Participación por categoría (puntos)</h3>
                    <canvas id="scatterChart" height="160"></canvas>
                    {{-- Alimentada desde dashboard.js, reutiliza labels/values del pie --}}
                </div>
            </div>

        </div>
    </div>

    {{-- ============================
         JS específico del dashboard
         (Breeze ya carga app.js; aquí sólo dashboard.js)
       ============================ --}}
    @vite('resources/js/dashboard.js')
</x-app-layout>
