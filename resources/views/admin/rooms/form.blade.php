@php
    $editing = $room->exists;
    $currentColor = old('color', $room->color?->value ?? 'blue');
    $selectedAmenities = $room->exists ? $room->amenities->pluck('id')->all() : [];
@endphp

<x-layouts.app :title="$editing ? 'Editar sala' : 'Nueva sala'">
    <x-page-header :title="$editing ? 'Editar '.$room->name : 'Nueva sala'">
        <x-slot:breadcrumbs>
            <a href="{{ route('admin.rooms.index') }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-white">Gestionar salas</a>
            <flux:icon.chevron-right variant="micro" class="text-zinc-300" />
            <span>{{ $editing ? $room->code() : 'Nueva' }}</span>
        </x-slot:breadcrumbs>
    </x-page-header>

    @if ($errors->any())
        <div role="alert" class="mb-6 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/50 dark:text-red-100">
            <flux:icon.exclamation-circle variant="mini" class="text-red-600" />
            {{ $errors->count() === 1 ? 'Hay un campo por corregir.' : 'Hay '.$errors->count().' campos por corregir.' }}
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}" class="max-w-3xl" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-800">
            <fieldset class="grid gap-6 p-5 md:grid-cols-[13rem_1fr]">
                <legend class="sr-only">Información</legend>
                <div>
                    <div class="text-sm font-semibold">Información</div>
                    <p class="mt-1 text-sm text-zinc-500">Cómo aparece la sala en el catálogo y el calendario.</p>
                </div>
                <div class="space-y-5">
                    <flux:field>
                        <flux:label>Nombre</flux:label>
                        <flux:input name="name" :value="old('name', $room->name)" required maxlength="100" placeholder="Sala Grupal B4" />
                        <flux:description>El código (por ejemplo B4) se toma del final del nombre.</flux:description>
                        <flux:error name="name" />
                        <flux:error name="slug" />
                    </flux:field>
                    <flux:input name="location" label="Ubicación" :value="old('location', $room->location)" placeholder="Biblioteca Central, piso 2" />
                    <flux:textarea name="description" label="Descripción" rows="3">{{ old('description', $room->description) }}</flux:textarea>

                    <fieldset>
                        <legend class="mb-2 text-sm font-medium">Color en el calendario</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Enums\RoomColor::cases() as $color)
                                <label class="cursor-pointer" title="{{ $color->label() }}">
                                    <input type="radio" name="color" value="{{ $color->value }}" class="peer sr-only" @checked($currentColor === $color->value)>
                                    <span class="grid size-8 place-items-center rounded-full ring-2 ring-transparent ring-offset-2 transition peer-checked:ring-zinc-900 peer-focus-visible:ring-brand dark:ring-offset-zinc-950 dark:peer-checked:ring-white">
                                        <span class="size-6 rounded-full {{ $color->dot() }}"></span>
                                    </span>
                                    <span class="sr-only">{{ $color->label() }}</span>
                                </label>
                            @endforeach
                        </div>
                        <flux:error name="color" />
                    </fieldset>
                </div>
            </fieldset>

            <fieldset class="grid gap-6 p-5 md:grid-cols-[13rem_1fr]">
                <legend class="sr-only">Equipamiento</legend>
                <div>
                    <div class="text-sm font-semibold">Equipamiento</div>
                    <p class="mt-1 text-sm text-zinc-500">Lo que tiene la sala y lo que se entrega al reservar.</p>
                </div>
                <div class="space-y-5">
                    @foreach ($amenities->groupBy(fn ($a) => $a->kind->value) as $kind => $group)
                        <div>
                            <div class="mb-2 text-sm font-medium">{{ $group->first()->kind->label() }}</div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($group as $amenity)
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-zinc-200 px-3 py-2 text-sm transition-colors has-[:checked]:border-brand has-[:checked]:bg-brand-soft dark:border-zinc-700">
                                        <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}" class="size-4 rounded border-zinc-300 text-brand accent-[var(--color-brand)]"
                                               @checked(in_array($amenity->id, array_map('intval', old('amenities', $selectedAmenities)), true))>
                                        <flux:icon :name="$amenity->icon" variant="micro" class="text-zinc-400" />
                                        {{ $amenity->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <flux:error name="amenities" />
                    <flux:error name="amenities.*" />

                    <flux:field>
                        <flux:label>Punto de retiro</flux:label>
                        <flux:input name="pickup_point" :value="old('pickup_point', $room->pickup_point)" placeholder="Mesón de préstamo, Biblioteca Central piso 1" maxlength="150" />
                        <flux:description>Dónde se retiran y devuelven la llave, los controles y los demás elementos.</flux:description>
                        <flux:error name="pickup_point" />
                    </flux:field>
                </div>
            </fieldset>

            <fieldset class="grid gap-6 p-5 md:grid-cols-[13rem_1fr]">
                <legend class="sr-only">Reglas de uso</legend>
                <div>
                    <div class="text-sm font-semibold">Reglas de uso</div>
                    <p class="mt-1 text-sm text-zinc-500">Los horarios van en bloques de 30 minutos.</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input name="capacity" type="number" min="1" max="200" label="Capacidad (personas)" :value="old('capacity', $room->capacity)" />
                    <flux:input name="max_duration_minutes" type="number" min="30" step="30" label="Duración máxima (min)" :value="old('max_duration_minutes', $room->max_duration_minutes)" />
                    <flux:input name="opens_at" type="time" step="1800" label="Abre" :value="old('opens_at', substr((string) $room->opens_at, 0, 5))" />
                    <flux:input name="closes_at" type="time" step="1800" label="Cierra" :value="old('closes_at', substr((string) $room->closes_at, 0, 5))" />
                </div>
            </fieldset>

            <fieldset class="grid gap-6 p-5 md:grid-cols-[13rem_1fr]">
                <legend class="sr-only">Publicación</legend>
                <div class="text-sm font-semibold">Publicación</div>
                <flux:field variant="inline">
                    <input type="hidden" name="is_active" value="0">
                    <flux:checkbox name="is_active" value="1" :checked="(bool) old('is_active', $room->is_active)" />
                    <flux:label>Sala activa: visible y disponible para reservar</flux:label>
                </flux:field>
            </fieldset>

            <div class="flex items-center justify-end gap-2 bg-zinc-50 px-5 py-3 dark:bg-zinc-900">
                <flux:button variant="ghost" :href="route('admin.rooms.index')" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary">{{ $editing ? 'Guardar cambios' : 'Crear sala' }}</flux:button>
            </div>
        </div>
    </form>
</x-layouts.app>
