@props(['reservation'])

@php($start = $reservation->localStart())

<a href="{{ route('reservations.show', $reservation) }}" wire:navigate
   class="group flex items-center gap-4 px-4 py-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-900">
    <div class="w-12 shrink-0 rounded-lg border border-zinc-200 text-center dark:border-zinc-700">
        <div class="rounded-t-lg bg-zinc-50 py-0.5 text-[10px] font-medium uppercase text-zinc-500 dark:bg-zinc-800">{{ rtrim($start->translatedFormat('M'), '.') }}</div>
        <div class="py-0.5 text-lg font-semibold leading-tight">{{ $start->format('j') }}</div>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <span class="size-2 shrink-0 rounded-full {{ $reservation->room->color->dot() }}"></span>
            <span class="truncate text-sm font-medium">{{ $reservation->room->name }}</span>
        </div>
        <div class="mt-0.5 text-xs text-zinc-500">
            {{ ucfirst($start->translatedFormat('l')) }} · <span class="tabular-nums">{{ $reservation->timeRangeLabel() }}</span> · {{ $reservation->room->location }}
        </div>
    </div>

    <x-status-badge :reservation="$reservation" />
    <flux:icon.chevron-right variant="micro" class="text-zinc-300 group-hover:text-zinc-500" />
</a>
