<flux:menu class="w-60">
    <div class="px-2 py-1.5">
        <div class="truncate text-sm font-medium">{{ auth()->user()->name }}</div>
        <div class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</div>
    </div>

    <flux:menu.separator />

    <flux:menu.item :href="route('settings.profile')" icon="user-circle" wire:navigate>Perfil</flux:menu.item>
    <flux:menu.item :href="route('settings.calendar')" icon="calendar" wire:navigate>Sincronizar calendario</flux:menu.item>
    <flux:menu.item :href="route('settings.appearance')" icon="sun" wire:navigate>Apariencia</flux:menu.item>

    <flux:menu.separator />

    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
            Cerrar sesión
        </flux:menu.item>
    </form>
</flux:menu>
