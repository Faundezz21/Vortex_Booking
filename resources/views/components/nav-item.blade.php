@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" wire:navigate
   @if ($active) aria-current="page" @endif
   {{ $attributes->class([
       'flex h-8 items-center gap-2.5 rounded-md px-2 text-sm transition-colors',
       'bg-white font-medium text-zinc-900 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-white dark:ring-zinc-700' => $active,
       'text-zinc-600 hover:bg-zinc-200/60 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-white' => ! $active,
   ]) }}>
    <flux:icon :name="$icon" variant="outline" class="size-4.5 shrink-0 {{ $active ? 'text-brand' : 'text-zinc-400 dark:text-zinc-500' }}" />
    <span class="truncate">{{ $slot }}</span>
</a>
