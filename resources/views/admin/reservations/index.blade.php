<x-layouts.app title="Todas las reservas">
    <x-page-header title="Todas las reservas" :subtitle="($filters['date'] ?? null) ? 'Reservas del día seleccionado.' : 'Reservas en curso y futuras. Filtra por día para ver el historial.'" />

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-zinc-200 p-3 dark:border-zinc-800">
            <select name="room" class="field" aria-label="Sala">
                <option value="">Todas las salas</option>
                @foreach ($rooms as $r)
                    <option value="{{ $r->id }}" @selected(($filters['room'] ?? null) == $r->id)>{{ $r->name }}</option>
                @endforeach
            </select>
            <select name="status" class="field" aria-label="Estado">
                <option value="">Todos los estados</option>
                @foreach (\App\Enums\ReservationStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="field" aria-label="Día">
            <flux:button type="submit" size="sm">Filtrar</flux:button>
            @if (array_filter($filters))
                <flux:button size="sm" variant="ghost" :href="route('admin.reservations.index')">Limpiar</flux:button>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium">Horario</th>
                        <th class="px-4 py-2 font-medium">Sala</th>
                        <th class="px-4 py-2 font-medium">Persona</th>
                        <th class="px-4 py-2 font-medium">Estado</th>
                        <th class="px-4 py-2"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($reservations as $reservation)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="whitespace-nowrap px-4 py-2.5">{{ ucfirst($reservation->localStart()->translatedFormat('D j M')) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 tabular-nums text-zinc-500">{{ $reservation->timeRangeLabel() }}</td>
                            <td class="px-4 py-2.5">
                                <span class="flex items-center gap-2"><span class="size-2 rounded-full {{ $reservation->room->color->dot() }}"></span>{{ $reservation->room->name }}</span>
                            </td>
                            <td class="px-4 py-2.5">
                                <div>{{ $reservation->user->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $reservation->user->email }}</div>
                            </td>
                            <td class="px-4 py-2.5"><x-status-badge :reservation="$reservation" /></td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button size="sm" variant="ghost" :href="route('reservations.show', $reservation)" wire:navigate>Ver</flux:button>
                                    @can('cancel', $reservation)
                                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}"
                                              onsubmit="return confirm('¿Cancelar la reserva de {{ e($reservation->user->name) }}? Se le avisará por correo.')">
                                            @csrf @method('PATCH')
                                            <flux:button type="submit" size="sm" variant="ghost" class="!text-red-600">Cancelar</flux:button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-zinc-500">No hay reservas con esos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reservations->links() }}</div>
</x-layouts.app>
