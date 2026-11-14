<x-layouts.app title="Gestionar salas">
    <x-page-header title="Gestionar salas" subtitle="Crea, edita o desactiva salas e imprime el QR de check-in de cada puerta.">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('admin.rooms.create')" wire:navigate>Nueva sala</flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <form method="GET" class="flex items-center gap-2 border-b border-zinc-200 p-3 dark:border-zinc-800">
            <flux:input name="q" :value="$search" icon="magnifying-glass" placeholder="Buscar por nombre o ubicación" size="sm" class="max-w-xs" />
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 font-medium">Sala</th>
                        <th class="px-4 py-2 text-right font-medium">Capacidad</th>
                        <th class="px-4 py-2 font-medium">Horario</th>
                        <th class="px-4 py-2 text-right font-medium">Máx.</th>
                        <th class="px-4 py-2 text-right font-medium">Próximas</th>
                        <th class="px-4 py-2 font-medium">Estado</th>
                        <th class="px-4 py-2"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($rooms as $room)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="size-2.5 shrink-0 rounded-full {{ $room->color->dot() }}"></span>
                                    <div>
                                        <div class="font-medium">{{ $room->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $room->location }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $room->capacity }}</td>
                            <td class="px-4 py-3 tabular-nums text-zinc-500">{{ $room->hoursLabel() }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-zinc-500">{{ $room->max_duration_minutes }} min</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $room->upcoming_count }}</td>
                            <td class="px-4 py-3">
                                @if ($room->is_active)
                                    <flux:badge size="sm" color="green">Activa</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Inactiva</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <flux:dropdown align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Acciones de {{ $room->name }}" />
                                        <flux:menu>
                                            <flux:menu.item icon="pencil-square" :href="route('admin.rooms.edit', $room)" wire:navigate>Editar</flux:menu.item>
                                            <flux:menu.item icon="qr-code" :href="route('admin.rooms.qr', $room)">QR de check-in</flux:menu.item>
                                            <flux:menu.item icon="calendar-days" :href="route('calendar', ['sala' => $room->slug])" wire:navigate>Ver semana</flux:menu.item>
                                            <flux:menu.separator />
                                            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}"
                                                  onsubmit="return confirm('¿Eliminar la sala «{{ e($room->name) }}»? Su historial de reservas se conserva.')">
                                                @csrf @method('DELETE')
                                                <flux:menu.item as="button" type="submit" icon="trash" variant="danger" class="w-full">Eliminar</flux:menu.item>
                                            </form>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-zinc-500">No se encontraron salas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $rooms->links() }}</div>
</x-layouts.app>
