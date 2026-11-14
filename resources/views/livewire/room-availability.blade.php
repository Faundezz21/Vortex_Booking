@php
    $selectedEnd = $startTime ? \Illuminate\Support\Carbon::createFromFormat('H:i', $startTime)->addMinutes($duration)->format('H:i') : null;
    $waiting = $this->waitingTimes;
@endphp

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <section aria-label="Disponibilidad" class="min-w-0 rounded-xl border border-zinc-200 dark:border-zinc-800">
        {{-- Selector de día --}}
        <div class="overflow-x-auto border-b border-zinc-200 px-2 [scrollbar-width:none] dark:border-zinc-800 [&::-webkit-scrollbar]:hidden">
            <div class="flex min-w-[40rem] gap-1 py-2">
                @foreach ($days as $d)
                    @php $value = $d->toDateString(); @endphp
                    <button type="button" wire:click="$set('date', '{{ $value }}')"
                            @class([
                                'flex min-w-11 flex-1 flex-col items-center rounded-lg py-1.5 transition-colors',
                                'bg-brand text-white' => $value === $date,
                                'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => $value !== $date,
                            ])
                            aria-pressed="{{ $value === $date ? 'true' : 'false' }}">
                        <span class="text-[11px] font-medium uppercase opacity-80">{{ $d->isToday() ? 'Hoy' : rtrim($d->translatedFormat('D'), '.') }}</span>
                        <span class="text-base font-semibold">{{ $d->format('j') }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold">{{ ucfirst($day->translatedFormat('l j \d\e F')) }}</h2>
                <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded border border-zinc-300 bg-white dark:border-zinc-600 dark:bg-zinc-900"></span>Libre</span>
                    <span class="flex items-center gap-1.5"><span class="hatched size-3 rounded bg-zinc-100 text-zinc-400 dark:bg-zinc-800"></span>Ocupado</span>
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded {{ $room->color->dot() }}"></span>Tuya</span>
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-brand"></span>Elegido</span>
                </div>
            </div>

            @if ($notice)
                <div class="mb-3 flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-900 dark:bg-blue-950/50 dark:text-blue-100">
                    <flux:icon.bell variant="mini" class="text-brand" /> {{ $notice }}
                </div>
            @endif

            <div class="grid grid-cols-3 gap-1.5 sm:grid-cols-4 xl:grid-cols-6" wire:loading.class="opacity-60">
                @foreach ($this->timeSlots as $slot)
                    @php
                        $selected = $startTime && $slot['time'] >= $startTime && $slot['time'] < $selectedEnd;
                        $isWaiting = in_array($slot['time'], $waiting, true);
                    @endphp

                    @if ($slot['state'] === 'busy' && ! $selected)
                        {{-- Ocupado: permite anotarse en la lista de espera --}}
                        <button type="button"
                                wire:click="{{ $isWaiting ? 'leaveWaitlist' : 'joinWaitlist' }}('{{ $slot['time'] }}')"
                                @class([
                                    'group relative h-10 rounded-lg px-2 text-left text-sm tabular-nums transition',
                                    'hatched bg-zinc-100 text-zinc-400 hover:text-zinc-600 dark:bg-zinc-800 dark:text-zinc-500' => ! $isWaiting,
                                    'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-300 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-800' => $isWaiting,
                                ])
                                title="{{ $isWaiting ? 'Estás en lista de espera. Clic para salir.' : 'Ocupado. Clic para avisarte si se libera.' }}"
                                aria-label="{{ $slot['time'] }}, ocupado. {{ $isWaiting ? 'En lista de espera; salir' : 'Avisarme si se libera' }}">
                            <span class="line-through decoration-zinc-300 group-hover:no-underline">{{ $slot['time'] }}</span>
                            <flux:icon :name="$isWaiting ? 'bell' : 'bell'" variant="micro" @class(['absolute right-2 top-1/2 -translate-y-1/2', 'opacity-0 group-hover:opacity-100' => ! $isWaiting]) />
                        </button>
                    @else
                        <button type="button"
                                @if ($slot['state'] === 'free') wire:click="selectSlot('{{ $slot['time'] }}')" @else disabled @endif
                                @class([
                                    'h-10 rounded-lg px-2 text-left text-sm font-medium tabular-nums transition',
                                    'bg-brand text-white shadow-sm' => $selected,
                                    'border border-zinc-200 bg-white hover:border-brand hover:text-brand dark:border-zinc-700 dark:bg-zinc-900' => ! $selected && $slot['state'] === 'free',
                                    'cursor-default text-white '.$room->color->dot() => ! $selected && $slot['state'] === 'mine',
                                    'cursor-not-allowed text-zinc-300 dark:text-zinc-700' => ! $selected && $slot['state'] === 'past',
                                ])
                                aria-pressed="{{ $selected ? 'true' : 'false' }}">
                            {{ $slot['time'] }}
                        </button>
                    @endif
                @endforeach
            </div>

            <p class="mt-3 text-xs text-zinc-500">
                {{ $this->freeCount }} bloques libres de {{ config('booking.slot_minutes') }} min.
                Si un bloque está ocupado, haz clic en él para que te avisemos si se libera.
            </p>
        </div>
    </section>

    {{-- Móvil: atajo al formulario --}}
    @if ($startTime)
        <a href="#reserva" class="fixed inset-x-3 bottom-3 z-20 flex items-center justify-between rounded-xl bg-zinc-900 px-4 py-3 text-white shadow-lg lg:hidden dark:bg-white dark:text-zinc-900">
            <span class="text-sm font-semibold tabular-nums">{{ $startTime }} – {{ $selectedEnd }}</span>
            <span class="text-sm">Continuar ↓</span>
        </a>
    @endif

    {{-- Formulario: POST tradicional validado por StoreReservationRequest --}}
    <aside @class(['min-w-0 scroll-mt-20', 'pb-20 lg:pb-0' => $startTime]) id="reserva">
        <form method="POST" action="{{ route('reservations.store') }}" class="sticky top-6 rounded-xl border border-zinc-200 dark:border-zinc-800">
            @csrf
            <input type="hidden" name="room_id" value="{{ $room->id }}">
            <input type="hidden" name="date" value="{{ $date }}">
            <input type="hidden" name="start_time" value="{{ $startTime }}">

            <div class="border-b border-zinc-200 p-4 dark:border-zinc-800">
                <h2 class="text-sm font-semibold">Nueva reserva</h2>
                <div class="mt-3 flex items-center gap-3">
                    <div class="grid size-10 place-items-center rounded-lg {{ $room->color->event() }} border-l-[3px]">
                        <flux:icon.clock variant="mini" />
                    </div>
                    <div>
                        @if ($startTime)
                            <div class="text-lg font-semibold tabular-nums">{{ $startTime }} – {{ $selectedEnd }}</div>
                        @else
                            <div class="text-lg font-semibold text-zinc-300 dark:text-zinc-600">--:-- – --:--</div>
                        @endif
                        <div class="text-xs text-zinc-500">{{ ucfirst($day->translatedFormat('l j \d\e F')) }}</div>
                    </div>
                </div>

                @foreach (['start_time', 'room_id', 'date', 'duration_minutes'] as $field)
                    @error($field)
                        <div class="mt-3 flex gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 dark:bg-red-950/50 dark:text-red-200">
                            <flux:icon.exclamation-circle variant="mini" class="mt-px shrink-0" /> {{ $message }}
                        </div>
                    @enderror
                @endforeach
            </div>

            <div class="space-y-4 p-4">
                @unless ($startTime)
                    <p class="text-sm text-zinc-500">Elige un bloque libre para continuar.</p>
                @endunless

                <flux:select wire:model.live="duration" name="duration_minutes" label="Duración" :disabled="! $startTime">
                    @foreach ($this->durationOptions as $minutes)
                        <flux:select.option :value="$minutes">
                            {{ $minutes < 60 ? $minutes.' minutos' : rtrim(rtrim(number_format($minutes / 60, 1, ',', ''), '0'), ',').' '.($minutes === 60 ? 'hora' : 'horas') }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="notes" name="notes" label="Nota (opcional)" rows="2" placeholder="Ej.: repaso para el certamen de cálculo" maxlength="500" />

                <flux:button type="submit" variant="primary" class="w-full" :disabled="! $startTime">Confirmar reserva</flux:button>
                <p class="text-center text-xs text-zinc-500">Recuerda hacer check-in con el QR de la puerta dentro de los primeros {{ config('booking.check_in.grace_minutes') }} minutos.</p>
            </div>
        </form>
    </aside>
</div>
