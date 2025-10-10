{{-- resources/views/sales/create.blade.php --}}
<x-app-layout>
    {{-- Encabezado de la página (Breeze) --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nueva venta
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4">

            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Formulario --}}
            <div class="rounded-xl border bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('sales.store') }}" class="grid gap-4">
                    @csrf

                    {{-- Categoría --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Categoría</label>
                        <select name="category_id" required class="rounded-md border-gray-300 w-full">
                            <option value="">Seleccione...</option>
                            @foreach($categorias as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Región --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Región</label>
                        <select name="region_id" required class="rounded-md border-gray-300 w-full">
                            <option value="">Seleccione...</option>
                            @foreach($regiones as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Fecha --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Fecha</label>
                        <input type="date" name="sold_at" value="{{ $hoy ?? now()->toDateString() }}" required
                               class="rounded-md border-gray-300 w-full">
                    </div>

                    {{-- Cantidad --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Cantidad</label>
                        <input type="number" name="quantity" min="1" value="1" required
                               class="rounded-md border-gray-300 w-full">
                    </div>

                    {{-- Monto --}}
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Monto (MXN)</label>
                        <input type="number" name="amount" step="0.01" min="0" required
                               class="rounded-md border-gray-300 w-full">
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                            Guardar
                        </button>
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center rounded-md border px-4 py-2 text-gray-700 border-gray-200 hover:bg-gray-50">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
