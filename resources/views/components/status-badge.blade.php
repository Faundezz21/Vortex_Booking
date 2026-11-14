@props(['reservation'])

@php
    $r = $reservation;
    [$label, $classes] = match (true) {
        $r->isCancelled() => ['Cancelada', 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'],
        $r->isNoShow() => ['No asistió', 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200 dark:bg-amber-950/50 dark:text-amber-200 dark:ring-amber-900'],
        $r->ends_at->isPast() => [$r->isCheckedIn() ? 'Asistió' : 'Finalizada', 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'],
        $r->hasStarted() => [$r->isCheckedIn() ? 'En uso' : 'Esperando check-in', 'bg-blue-50 text-blue-800 ring-1 ring-inset ring-blue-200 dark:bg-blue-950/50 dark:text-blue-200 dark:ring-blue-900'],
        default => ['Confirmada', 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-900'],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium', $classes]) }}>{{ $label }}</span>
