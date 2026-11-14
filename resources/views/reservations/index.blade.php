<x-layouts.app title="Mis reservas">
    <x-page-header title="Mis reservas" :subtitle="'Tienes '.$upcoming->count().' de '.$maxActive.' reservas activas permitidas.'">
        <x-slot:actions>
            <flux:button icon="calendar" :href="route('settings.calendar')" wire:navigate>Sincronizar calendario</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('rooms.index')" wire:navigate>Nueva reserva</flux:button>
        </x-slot:actions>
    </x-page-header>

    <section class="mb-8 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 text-sm font-semibold dark:border-zinc-800 dark:bg-zinc-900">Próximas</div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($upcoming as $reservation)
                <x-reservation-row :reservation="$reservation" />
            @empty
                <div class="px-4 py-12 text-center">
                    <p class="text-sm font-medium">No tienes reservas próximas</p>
                    <p class="mt-1 text-sm text-zinc-500">Busca un espacio libre en el <a href="{{ route('calendar') }}" wire:navigate class="font-medium text-brand hover:underline">calendario</a>.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 text-sm font-semibold dark:border-zinc-800 dark:bg-zinc-900">Historial</div>
        @if ($history->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-zinc-500">Aquí aparecerán tus reservas pasadas y canceladas.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs text-zinc-500">
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="px-4 py-2 font-medium">Fecha</th>
                            <th class="px-4 py-2 font-medium">Horario</th>
                            <th class="px-4 py-2 font-medium">Sala</th>
                            <th class="px-4 py-2 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($history as $reservation)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                <td class="px-4 py-2.5 tabular-nums">{{ $reservation->localStart()->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 tabular-nums text-zinc-500">{{ $reservation->timeRangeLabel() }}</td>
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('reservations.show', $reservation) }}" wire:navigate class="flex items-center gap-2 hover:underline">
                                        <span class="size-2 rounded-full {{ $reservation->room->color->dot() }}"></span>{{ $reservation->room->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-2.5">
                                    <x-status-badge :reservation="$reservation" />
                                    @if ($reservation->isCancelled() && $reservation->cancelled_by !== $reservation->user_id)
                                        <span class="ml-1 text-xs text-zinc-500">por administración</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">{{ $history->links() }}</div>
        @endif
    </section>
</x-layouts.app>
