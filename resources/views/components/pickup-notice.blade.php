@props(['room', 'compact' => false])

@php $items = $room->pickupItems(); @endphp

@if ($items->isNotEmpty())
    <div {{ $attributes->class('rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-3 text-sm text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100') }}>
        <div class="flex items-center gap-2 font-medium">
            <flux:icon.key variant="mini" class="text-amber-600 dark:text-amber-400" />
            Retira antes de entrar
        </div>
        <ul class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($items as $item)
                <li class="inline-flex items-center gap-1 rounded-md bg-white/70 px-2 py-0.5 text-xs ring-1 ring-amber-200 dark:bg-amber-900/30 dark:ring-amber-800">
                    <flux:icon :name="$item->icon" variant="micro" class="size-3.5 opacity-70" />{{ $item->name }}
                </li>
            @endforeach
        </ul>
        @if ($room->pickup_point)
            <p class="mt-2 text-xs text-amber-800 dark:text-amber-200/80">
                En <strong class="font-semibold">{{ $room->pickup_point }}</strong>@unless ($compact). Devuélvelos ahí mismo al terminar.@endunless
            </p>
        @endif
    </div>
@endif
