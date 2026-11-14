<x-layouts.app :title="$room->name">
    <x-page-header :title="$room->name" :subtitle="$room->description">
        <x-slot:breadcrumbs>
            <a href="{{ route('rooms.index') }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-white">Salas</a>
            <flux:icon.chevron-right variant="micro" class="text-zinc-300" />
            <span class="flex items-center gap-1.5"><span class="size-2 rounded-full {{ $room->color->dot() }}"></span>{{ $room->code() }}</span>
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <flux:button icon="calendar-days" :href="route('calendar', ['sala' => $room->slug])" wire:navigate>Ver semana</flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 flex flex-wrap gap-2 text-sm">
        @foreach ([
            ['map-pin', $room->location ?? 'Sin ubicación'],
            ['users', $room->capacity.' '.($room->capacity === 1 ? 'persona' : 'personas')],
            ['clock', $room->hoursLabel()],
            ['arrows-right-left', 'Hasta '.rtrim(rtrim(number_format($room->max_duration_minutes / 60, 1, ',', ''), '0'), ',').' h por reserva'],
        ] as [$icon, $text])
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-2.5 py-1 text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                <flux:icon :name="$icon" variant="micro" class="text-zinc-400" />{{ $text }}
            </span>
        @endforeach
    </div>

    @if ($room->equipment()->isNotEmpty() || $room->pickupItems()->isNotEmpty())
        <div class="mb-6 grid gap-3 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                <div class="mb-2.5 text-sm font-semibold">Equipamiento de la sala</div>
                @if ($room->equipment()->isNotEmpty())
                    <ul class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                        @foreach ($room->equipment() as $item)
                            <li class="flex items-center gap-1.5"><flux:icon :name="$item->icon" variant="mini" class="size-4 text-zinc-400" />{{ $item->name }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-zinc-500">Sin equipamiento especial.</p>
                @endif
            </div>
            <x-pickup-notice :room="$room" />
        </div>
    @endif

    @unless ($room->is_active)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Esta sala está desactivada y no acepta reservas nuevas.</div>
    @else
        <livewire:room-availability :room="$room" />
    @endunless
</x-layouts.app>
