<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Perfil</h2>
  </x-slot>

  <div class="py-8 max-w-3xl mx-auto px-4">
    @if (session('status') === 'profile-updated')
      <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-green-800">
        Perfil actualizado.
      </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
      @csrf @method('patch')

      <div>
        <label class="block text-sm text-gray-600">Nombre</label>
        <input name="name" value="{{ old('name', $user->name) }}" class="mt-1 rounded-md border-gray-300 w-full">
        @error('name')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm text-gray-600">Email</label>
        <input name="email" value="{{ old('email', $user->email) }}" class="mt-1 rounded-md border-gray-300 w-full">
        @error('email')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
      </div>

      <div class="flex gap-2">
        <button class="px-4 py-2 rounded bg-indigo-600 text-white">Guardar</button>
        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded border">Cancelar</a>
      </div>
    </form>

    <hr class="my-8">

    <form method="POST" action="{{ route('profile.destroy') }}"
          onsubmit="return confirm('¿Eliminar tu cuenta? Esta acción es irreversible.');">
      @csrf @method('delete')

      <label class="block text-sm text-gray-600">Confirma tu contraseña</label>
      <input type="password" name="password" class="mt-1 rounded-md border-gray-300 w-full max-w-sm" required>

      <button class="mt-3 px-4 py-2 rounded bg-red-600 text-white">Eliminar cuenta</button>
    </form>
  </div>
</x-app-layout>
