{{-- Selector de tema claro / oscuro. Flux guarda la elección en el navegador y la aplica
     antes de pintar la página (@fluxAppearance), así no hay parpadeo. Mientras la persona
     no elija, se usa el tema del sistema y el botón marcado refleja el tema visible. --}}
<div x-data {{ $attributes->class('inline-flex items-center gap-0.5 rounded-lg border border-zinc-200 bg-zinc-100/70 p-0.5 dark:border-zinc-700 dark:bg-zinc-800') }}
     role="radiogroup" aria-label="Tema de la interfaz">
    @foreach ([[false, 'sun', 'Claro'], [true, 'moon', 'Oscuro']] as [$dark, $icon, $label])
        @php $state = $dark ? '$flux.dark' : '! $flux.dark'; @endphp
        <button type="button" role="radio" title="{{ $label }}" aria-label="{{ $label }}"
                x-on:click="$flux.appearance = '{{ $dark ? 'dark' : 'light' }}'"
                :aria-checked="{{ $state }}"
                :class="{{ $state }}
                    ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-600 dark:text-white'
                    : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'"
                class="grid size-7 place-items-center rounded-md transition-colors">
            <flux:icon :name="$icon" variant="mini" class="size-4" />
        </button>
    @endforeach
</div>
