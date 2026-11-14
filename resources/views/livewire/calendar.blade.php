@php
    [$startHour, $endHour] = $this->bounds;
    $hourHeight = 48;
    $gridHeight = ($endHour - $startHour) * $hourHeight;
    $columns = $this->columns;
    $isWeek = $view === 'semana';
@endphp

<div>
    <x-page-header title="Calendario" :subtitle="$isWeek ? 'Semana de una sala. Haz clic en un espacio libre para reservarlo.' : 'Todas las salas en un día. Haz clic en un espacio libre para reservarlo.'">
        <x-slot:actions>
            <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-700 dark:bg-zinc-900" role="tablist">
                @foreach (['semana' => 'Semana', 'dia' => 'Día'] as $key => $label)
                    <button type="button" wire:click="setView('{{ $key }}')" role="tab" aria-selected="{{ $view === $key ? 'true' : 'false' }}"
                            @class(['rounded-md px-3 py-1 text-sm', 'bg-white font-medium shadow-xs dark:bg-zinc-700' => $view === $key, 'text-zinc-500 hover:text-zinc-900 dark:hover:text-white' => $view !== $key])>{{ $label }}</button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- Barra de navegación --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <flux:button size="sm" wire:click="goToday">Hoy</flux:button>
        <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previous" aria-label="Anterior" />
        <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="next" aria-label="Siguiente" />
        <h2 class="ml-1 text-lg font-semibold">{{ $this->rangeLabel }}</h2>
        <span wire:loading class="ml-2 text-xs text-zinc-400">Cargando…</span>

        @if ($isWeek)
            <div class="ml-auto flex flex-wrap gap-1.5">
                @foreach ($this->rooms as $r)
                    <button type="button" wire:click="selectRoom('{{ $r->slug }}')"
                            @class([
                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs transition-colors',
                                'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $r->slug === $room,
                                'border-zinc-200 text-zinc-600 hover:border-zinc-300 dark:border-zinc-700 dark:text-zinc-300' => $r->slug !== $room,
                            ])>
                        <span class="size-2 rounded-full {{ $r->color->dot() }}"></span>{{ $r->code() }}
                        <span class="hidden xl:inline">· {{ str($r->name)->beforeLast(' ') }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if (empty($columns))
        <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center text-sm text-zinc-500 dark:border-zinc-700">No hay salas activas para mostrar.</div>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800" wire:loading.class="opacity-60">
            <div class="min-w-[44rem]">
                {{-- Encabezados de columna --}}
                <div class="sticky top-0 z-10 flex border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="w-14 shrink-0"></div>
                    @foreach ($columns as $col)
                        <div class="min-w-0 flex-1 border-l border-zinc-200 px-2 py-2 dark:border-zinc-800">
                            @if ($isWeek)
                                <button type="button" wire:click="openDay('{{ $col['date']->toDateString() }}')" class="flex w-full flex-col items-center gap-0.5 rounded-md py-0.5 hover:bg-zinc-50 dark:hover:bg-zinc-900" title="Ver el día con todas las salas">
                                    <span class="text-[11px] font-medium uppercase text-zinc-500">{{ rtrim($col['date']->translatedFormat('D'), '.') }}</span>
                                    <span @class(['grid size-8 place-items-center rounded-full text-base font-semibold', 'bg-brand text-white' => $col['isToday']])>{{ $col['date']->format('j') }}</span>
                                </button>
                            @else
                                <a href="{{ route('rooms.show', $col['room']) }}" wire:navigate class="flex items-center gap-2 rounded-md px-1 py-1.5 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                                    <span class="size-2.5 shrink-0 rounded-full {{ $col['room']->color->dot() }}"></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium">{{ $col['room']->name }}</span>
                                        <span class="block text-[11px] text-zinc-500">{{ $col['room']->capacity }} pers. · {{ $col['room']->hoursLabel() }}</span>
                                    </span>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Cuerpo --}}
                <div class="flex">
                    <div class="relative w-14 shrink-0" style="height: {{ $gridHeight }}px">
                        @for ($h = $startHour; $h < $endHour; $h++)
                            <div class="absolute right-2 -translate-y-1/2 text-[11px] tabular-nums text-zinc-400" style="top: {{ ($h - $startHour) * $hourHeight }}px">
                                @if ($h > $startHour) {{ sprintf('%02d:00', $h) }} @endif
                            </div>
                        @endfor
                    </div>

                    @foreach ($columns as $col)
                        @php $room = $col['room']; @endphp
                        <div class="relative min-w-0 flex-1 border-l border-zinc-200 dark:border-zinc-800" style="height: {{ $gridHeight }}px">
                            {{-- Líneas de hora --}}
                            @for ($h = $startHour + 1; $h < $endHour; $h++)
                                <div class="pointer-events-none absolute inset-x-0 border-t border-zinc-100 dark:border-zinc-800/70" style="top: {{ ($h - $startHour) * $hourHeight }}px"></div>
                            @endfor

                            {{-- Fuera de horario --}}
                            @if ($col['openTop'] > 0)
                                <div class="hatched absolute inset-x-0 top-0 bg-zinc-50 text-zinc-400 dark:bg-zinc-900" style="height: {{ $col['openTop'] }}%"></div>
                            @endif
                            @if ($col['closedTop'] < 100)
                                <div class="hatched absolute inset-x-0 bottom-0 bg-zinc-50 text-zinc-400 dark:bg-zinc-900" style="top: {{ $col['closedTop'] }}%"></div>
                            @endif

                            {{-- Bloques libres: clic para reservar --}}
                            @foreach ($col['slots'] as $slot)
                                <a href="{{ $slot['url'] }}" wire:navigate
                                   class="group absolute inset-x-0.5 flex items-center rounded px-1.5 text-[11px] font-medium text-brand opacity-0 transition hover:bg-brand-soft hover:opacity-100 focus:bg-brand-soft focus:opacity-100"
                                   style="top: {{ $slot['top'] }}%; height: {{ $slot['height'] }}%"
                                   aria-label="Reservar {{ $room->name }} a las {{ $slot['label'] }}">+ {{ $slot['label'] }}</a>
                            @endforeach

                            {{-- Reservas --}}
                            @foreach ($col['events'] as $event)
                                <{{ $event['url'] ? 'a' : 'div' }}
                                    @if ($event['url']) href="{{ $event['url'] }}" wire:navigate @endif
                                    @class([
                                        'absolute inset-x-1 overflow-hidden rounded-md border-l-[3px] px-1.5 py-1 text-[11px] leading-tight',
                                        $room->color->event(),
                                        'shadow-sm ring-1 ring-inset ring-current/30 hover:brightness-95' => $event['mine'],
                                        'opacity-80' => ! $event['mine'],
                                    ])
                                    style="top: calc({{ $event['top'] }}% + 1px); height: calc({{ $event['height'] }}% - 2px)">
                                    <div class="flex items-center gap-1 font-semibold">
                                        @if ($event['mine'] && $event['checkedIn'])<flux:icon.check-circle variant="micro" class="size-3" />@endif
                                        <span class="truncate">{{ $event['title'] }}</span>
                                    </div>
                                    <div class="truncate tabular-nums opacity-75">{{ $event['time'] }}</div>
                                </{{ $event['url'] ? 'a' : 'div' }}>
                            @endforeach

                            {{-- Línea "ahora" --}}
                            @if ($col['isToday'] && $this->nowTop !== null)
                                <div class="pointer-events-none absolute inset-x-0 z-[5] border-t-2 border-red-500" style="top: {{ $this->nowTop }}%">
                                    <span class="absolute -left-1 -top-[5px] size-2 rounded-full bg-red-500"></span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-zinc-500">
            <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded-sm border-l-[3px] border-blue-500 bg-blue-50 ring-1 ring-inset ring-blue-500/30"></span>Tu reserva</span>
            <span class="flex items-center gap-1.5"><span class="h-3 w-4 rounded-sm border-l-[3px] border-blue-500 bg-blue-50 opacity-80"></span>Reservado</span>
            <span class="flex items-center gap-1.5"><span class="hatched h-3 w-4 rounded-sm bg-zinc-50 text-zinc-400"></span>Cerrado</span>
            <span class="flex items-center gap-1.5"><span class="h-0.5 w-4 bg-red-500"></span>Ahora</span>
        </div>
    @endif
</div>
