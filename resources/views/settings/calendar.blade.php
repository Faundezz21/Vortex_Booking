<x-layouts.app title="Sincronizar calendario">
    @include('partials.settings-heading')

    <x-settings.layout heading="Sincronizar calendario" subheading="Ve tus reservas en Google Calendar, Outlook o Apple Calendar. Se actualizan solas cuando reservas o cancelas.">
        <div class="space-y-6">
            <div x-data="{ copied: false }">
                <flux:label>Tu enlace privado (iCal)</flux:label>
                <div class="mt-2 flex gap-2">
                    <input type="text" readonly value="{{ $feedUrl }}" x-ref="url" class="field min-w-0 flex-1 font-mono text-xs" onclick="this.select()">
                    <flux:button icon="clipboard-document" x-on:click="navigator.clipboard.writeText($refs.url.value); copied = true; setTimeout(() => copied = false, 2000)">
                        <span x-text="copied ? 'Copiado' : 'Copiar'">Copiar</span>
                    </flux:button>
                </div>
                <p class="mt-2 text-xs text-zinc-500">No compartas este enlace: cualquiera que lo tenga puede ver tus reservas.</p>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800">
                @foreach ([
                    ['Google Calendar', 'Otros calendarios → "+" → Desde URL → pega el enlace.'],
                    ['Outlook', 'Agregar calendario → Suscribirse desde la Web → pega el enlace.'],
                    ['Apple Calendar', 'Archivo → Nueva suscripción a calendario → pega el enlace.'],
                ] as [$app, $steps])
                    <div class="flex gap-3 border-b border-zinc-100 px-4 py-3 text-sm last:border-0 dark:border-zinc-800">
                        <span class="w-32 shrink-0 font-medium">{{ $app }}</span>
                        <span class="text-zinc-500">{{ $steps }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                <div>
                    <div class="text-sm font-medium">Generar un enlace nuevo</div>
                    <div class="text-sm text-zinc-500">Úsalo si compartiste el enlace por error. El anterior dejará de funcionar.</div>
                </div>
                <form method="POST" action="{{ route('settings.calendar.regenerate') }}" onsubmit="return confirm('El enlace actual dejará de funcionar. ¿Continuar?')">
                    @csrf
                    <flux:button type="submit" icon="arrow-path">Regenerar</flux:button>
                </form>
            </div>
        </div>
    </x-settings.layout>
</x-layouts.app>
