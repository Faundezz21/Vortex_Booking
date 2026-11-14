<x-layouts.app :title="'QR · '.$room->name">
    <div class="print:hidden">
        <x-page-header title="QR de check-in" subtitle="Imprime este afiche y pégalo en la puerta de la sala. Escanearlo es la forma de registrar la llegada.">
            <x-slot:breadcrumbs>
                <a href="{{ route('admin.rooms.index') }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-white">Gestionar salas</a>
                <flux:icon.chevron-right variant="micro" class="text-zinc-300" />
                <span>{{ $room->code() }}</span>
            </x-slot:breadcrumbs>
            <x-slot:actions>
                <form method="POST" action="{{ route('admin.rooms.qr.regenerate', $room) }}" onsubmit="return confirm('El QR actual dejará de funcionar. ¿Generar uno nuevo?')">
                    @csrf
                    <flux:button type="submit" icon="arrow-path">Generar nuevo QR</flux:button>
                </form>
                <flux:button variant="primary" icon="printer" onclick="window.print()">Imprimir</flux:button>
            </x-slot:actions>
        </x-page-header>
    </div>

    {{-- Afiche (tamaño carta al imprimir) --}}
    <div class="mx-auto max-w-md rounded-2xl border border-zinc-200 bg-white p-8 text-center text-zinc-900 print:max-w-none print:border-0 print:p-0">
        <x-app-logo />
        <div class="mt-8 flex items-center justify-center gap-2">
            <span class="size-3 rounded-full {{ $room->color->dot() }}"></span>
            <span class="text-sm font-medium text-zinc-500">{{ $room->code() }}</span>
        </div>
        <h1 class="mt-1 text-3xl font-bold tracking-tight">{{ $room->name }}</h1>
        <p class="text-zinc-500">{{ $room->location }}</p>

        <div class="mx-auto mt-6 w-64 [&_svg]:h-auto [&_svg]:w-full">{!! $qrSvg !!}</div>

        <p class="mt-6 text-lg font-semibold">¿Tienes una reserva? Escanea al llegar</p>
        <p class="mt-1 text-sm text-zinc-500">Tienes {{ config('booking.check_in.grace_minutes') }} minutos desde el inicio. Si no registras tu llegada, la sala se libera para otras personas.</p>
    </div>

    <p class="mt-4 text-center text-xs text-zinc-400 print:hidden">El QR apunta a una URL con un token secreto de la sala.</p>
</x-layouts.app>
