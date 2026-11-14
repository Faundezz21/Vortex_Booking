@props(['room', 'compact' => false])

@php($next = \App\Support\RoomSchedule::nextFreeToday($room))

<a href="{{ route('rooms.show', $room) }}" wire:navigate
   class="group flex items-center gap-4 px-4 py-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-900">
    <span class="size-2.5 shrink-0 rounded-full {{ $room->color->dot() }}" aria-hidden="true"></span>

    <span class="min-w-0 flex-1">
        <span class="flex items-center gap-2">
            <span class="truncate text-sm font-medium">{{ $room->name }}</span>
            <span class="rounded bg-zinc-100 px-1.5 py-px font-mono text-[11px] text-zinc-500 dark:bg-zinc-800">{{ $room->code() }}</span>
        </span>
        <span class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
            <span class="truncate">{{ $room->location }}</span>
            @if (! $compact && $room->relationLoaded('amenities') && $room->amenities->isNotEmpty())
                <span class="hidden shrink-0 items-center gap-1 text-zinc-400 sm:flex" aria-label="Equipamiento">
                    @foreach ($room->equipment()->take(5) as $item)
                        <span title="{{ $item->name }}"><flux:icon :name="$item->icon" variant="micro" class="size-3.5" /></span>
                    @endforeach
                    @if ($room->pickupItems()->isNotEmpty())
                        <span title="Se retira: {{ $room->pickupItems()->pluck('name')->implode(', ') }}" class="ml-0.5 text-amber-500"><flux:icon.key variant="micro" class="size-3.5" /></span>
                    @endif
                </span>
            @endif
        </span>
    </span>

    @unless ($compact)
        <span class="hidden w-24 items-center gap-1 text-xs text-zinc-500 sm:flex"><flux:icon.users variant="micro" /> {{ $room->capacity }}</span>
        <span class="hidden w-28 text-xs tabular-nums text-zinc-500 md:block">{{ $room->hoursLabel() }}</span>
    @endunless

    <span class="w-28 text-right text-xs">
        @if ($next)
            <span class="font-medium text-emerald-700 dark:text-emerald-400">Libre {{ $next }}</span>
        @else
            <span class="text-zinc-400">Sin bloques hoy</span>
        @endif
    </span>

    <flux:icon.chevron-right variant="micro" class="text-zinc-300 group-hover:text-zinc-500" />
</a>
