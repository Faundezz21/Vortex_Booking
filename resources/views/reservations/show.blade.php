@php
    $r = $reservation;
    $tz = config('app.display_timezone');
    $isOwner = $r->user_id === auth()->id();
@endphp

<x-layouts.app :title="'Reserva · '.$r->room->name">
    <x-page-header :title="$r->room->name">
        <x-slot:breadcrumbs>
            <a href="{{ route('reservations.index') }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-white">Mis reservas</a>
            <flux:icon.chevron-right variant="micro" class="text-zinc-300" />
            <span>#{{ $r->id }}</span>
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @if ($r->isConfirmed())
                <flux:button icon="arrow-down-tray" :href="route('reservations.ics', $r)">Agregar a mi calendario</flux:button>
            @endif
            @can('cancel', $r)
                <form method="POST" action="{{ route('reservations.cancel', $r) }}" onsubmit="return confirm('¿Cancelar esta reserva? El bloque quedará libre para otras personas.')">
                    @csrf @method('PATCH')
                    <flux:button type="submit" variant="danger" icon="x-mark">Cancelar reserva</flux:button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800">
            <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ([
                    'Estado' => null,
                    'Fecha' => ucfirst($r->localStart()->translatedFormat('l j \d\e F Y')),
                    'Horario' => $r->timeRangeLabel().' ('.$r->durationMinutes().' min)',
                    'Sala' => $r->room->name.' · '.$r->room->code(),
                    'Ubicación' => $r->room->location ?? '—',
                    'Reservada por' => $isOwner ? 'Tú' : $r->user->name.' ('.$r->user->email.')',
                    'Nota' => $r->notes ?: '—',
                ] as $label => $value)
                    <div class="grid grid-cols-[8rem_1fr] gap-4 px-4 py-3 text-sm">
                        <dt class="text-zinc-500">{{ $label }}</dt>
                        <dd>
                            @if ($label === 'Estado')
                                <x-status-badge :reservation="$r" />
                                @if ($r->isCancelled() && $r->cancelledBy)
                                    <span class="ml-2 text-xs text-zinc-500">por {{ $r->cancelled_by === $r->user_id ? 'ti' : 'administración' }}, {{ $r->cancelled_at->setTimezone($tz)->format('d/m H:i') }}</span>
                                @endif
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="space-y-4">
        @if ($r->isConfirmed() && $r->ends_at->isFuture())
            <x-pickup-notice :room="$r->room" />
        @endif

        {{-- Check-in --}}
        <aside class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
            <h2 class="flex items-center gap-2 text-sm font-semibold"><flux:icon.qr-code variant="mini" class="text-zinc-400" /> Asistencia</h2>

            @if ($r->isCheckedIn())
                <div class="mt-3 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100">
                    <flux:icon.check-circle variant="mini" class="text-emerald-600" />
                    Llegada registrada a las {{ $r->checked_in_at->setTimezone($tz)->format('H:i') }}
                </div>
            @elseif ($r->isNoShow())
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">No se registró asistencia a tiempo, así que la sala se liberó a las {{ $r->released_at?->setTimezone($tz)->format('H:i') }}.</p>
            @elseif ($r->isConfirmed() && $r->ends_at->isFuture())
                <ol class="mt-3 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                    <li class="flex gap-2.5"><span class="grid size-5 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-semibold dark:bg-zinc-800">1</span>Llega a la sala desde las {{ $r->checkInOpensAt()->setTimezone($tz)->format('H:i') }}.</li>
                    <li class="flex gap-2.5"><span class="grid size-5 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-semibold dark:bg-zinc-800">2</span>Escanea con tu celular el código QR pegado en la puerta.</li>
                    <li class="flex gap-2.5"><span class="grid size-5 shrink-0 place-items-center rounded-full bg-zinc-100 text-xs font-semibold dark:bg-zinc-800">3</span>Confirma tu llegada antes de las <strong class="font-semibold text-zinc-900 dark:text-white">{{ $r->checkInDeadline()->setTimezone($tz)->format('H:i') }}</strong>.</li>
                </ol>
                <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                    Si no registras tu llegada en los primeros {{ config('booking.check_in.grace_minutes') }} minutos, la reserva se libera automáticamente y avisamos a la lista de espera.
                </p>
            @else
                <p class="mt-3 text-sm text-zinc-500">No aplica para esta reserva.</p>
            @endif
        </aside>
        </div>
    </div>
</x-layouts.app>
