@php($tz = config('app.display_timezone'))

<x-layouts.app :title="'Check-in · '.$room->name">
    <div class="mx-auto max-w-md pt-4">
        <div class="rounded-2xl border border-zinc-200 p-6 text-center dark:border-zinc-800">
            <div class="mx-auto mb-4 grid size-12 place-items-center rounded-xl {{ $room->color->event() }} border-l-4">
                <flux:icon.qr-code />
            </div>
            <div class="text-sm text-zinc-500">Check-in</div>
            <h1 class="mt-1 text-xl font-semibold">{{ $room->name }}</h1>
            <p class="text-sm text-zinc-500">{{ $room->location }}</p>

            <div class="mt-6 text-left">
                @if (! $reservation)
                    <div class="rounded-lg bg-zinc-50 p-4 text-sm dark:bg-zinc-900">
                        <p class="font-medium">No tienes una reserva en esta sala ahora.</p>
                        <p class="mt-1 text-zinc-500">El check-in se abre {{ config('booking.check_in.opens_minutes_before') }} minutos antes del inicio de tu reserva.</p>
                    </div>
                    <flux:button class="mt-4 w-full" :href="route('rooms.show', $room)" wire:navigate>Ver disponibilidad de la sala</flux:button>
                @elseif ($reservation->isCheckedIn())
                    <div class="flex items-center gap-2 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100">
                        <flux:icon.check-circle class="text-emerald-600" />
                        Ya registraste tu llegada ({{ $reservation->timeRangeLabel() }}). ¡Buen estudio!
                    </div>
                @else
                    <div class="rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-800">
                        <div class="text-zinc-500">Tu reserva</div>
                        <div class="mt-0.5 text-lg font-semibold tabular-nums">{{ $reservation->timeRangeLabel() }}</div>
                        <div class="text-zinc-500">Confirma antes de las {{ $reservation->checkInDeadline()->setTimezone($tz)->format('H:i') }}</div>
                    </div>
                    <form method="POST" action="{{ route('check-in.store', ['room' => $room, 'k' => $token]) }}" class="mt-4">
                        @csrf
                        <flux:button type="submit" variant="primary" class="w-full" icon="check">Registrar mi llegada</flux:button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
