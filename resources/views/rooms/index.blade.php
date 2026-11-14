<x-layouts.app title="Salas">
    <x-page-header title="Salas" subtitle="Elige una sala para ver su disponibilidad y reservar.">
        <x-slot:actions>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <select name="con" onchange="this.form.submit()" class="field" aria-label="Equipamiento">
                    <option value="">Cualquier equipamiento</option>
                    @foreach ($equipment as $item)
                        <option value="{{ $item->slug }}" @selected($amenity === $item->slug)>Con {{ mb_strtolower($item->name) }}</option>
                    @endforeach
                </select>
                <select name="capacity" onchange="this.form.submit()" class="field" aria-label="Capacidad">
                    <option value="0" @selected($minCapacity === 0)>Cualquier capacidad</option>
                    @foreach ([2, 4, 6, 8, 10] as $n)
                        <option value="{{ $n }}" @selected($minCapacity === $n)>{{ $n }}+ personas</option>
                    @endforeach
                </select>
                @if ($amenity !== '' || $minCapacity > 0)
                    <flux:button size="sm" variant="ghost" :href="route('rooms.index')">Limpiar</flux:button>
                @endif
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <div class="hidden items-center gap-4 border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-xs font-medium text-zinc-500 sm:flex dark:border-zinc-800 dark:bg-zinc-900">
            <span class="w-2.5"></span>
            <span class="flex-1">Sala</span>
            <span class="w-24">Capacidad</span>
            <span class="hidden w-28 md:block">Horario</span>
            <span class="w-28 text-right">Hoy</span>
            <span class="w-4"></span>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($rooms as $room)
                <x-room-row :room="$room" />
            @empty
                <p class="px-4 py-12 text-center text-sm text-zinc-500">Ninguna sala cumple ese filtro.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
