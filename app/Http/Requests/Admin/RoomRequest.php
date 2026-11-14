<?php

namespace App\Http\Requests\Admin;

use App\Enums\AmenityKind;
use App\Enums\RoomColor;
use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// reglas de validacion para crear y editar salas
class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $room = $this->route('room');

        return $room instanceof Room
            ? $this->user()->can('update', $room)
            : $this->user()->can('create', Room::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
            'color' => $this->input('color') ?: RoomColor::Blue->value,
        ]);
    }

    // reglas de validacion del formulario
    public function rules(): array
    {
        $room = $this->route('room');
        $slot = config('booking.slot_minutes');

        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'slug' => ['required', Rule::unique('rooms', 'slug')->ignore($room?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:150'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
            'max_duration_minutes' => ['required', 'integer', "min:$slot", 'max:720', "multiple_of:$slot"],
            'is_active' => ['boolean'],
            'color' => ['required', Rule::enum(RoomColor::class)],
            'pickup_point' => ['nullable', 'string', 'max:150'],
            'amenities' => ['array'],
            'amenities.*' => ['integer', 'distinct', Rule::exists('amenities', 'id')],
        ];
    }

    // retorna la lista de ids de comodidades seleccionadas
    public function amenityIds(): array
    {
        return array_map('intval', $this->validated('amenities', []));
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // exige punto de retiro cuando hay comodidades de entrega fisica
                $ids = (array) $this->input('amenities', []);
                if ($ids && blank($this->input('pickup_point'))
                    && Amenity::whereIn('id', $ids)->where('kind', AmenityKind::Pickup)->exists()) {
                    $validator->errors()->add('pickup_point', 'Indica dónde se retiran la llave y los demás elementos (por ejemplo, "Mesón de préstamo, piso 1").');
                }
            },
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['opens_at', 'closes_at'])) {
                    return;
                }

                $slot = config('booking.slot_minutes');
                foreach (['opens_at', 'closes_at'] as $field) {
                    [, $minutes] = explode(':', $this->input($field));
                    if ((int) $minutes % $slot !== 0) {
                        $validator->errors()->add($field, "La hora debe coincidir con bloques de $slot minutos (por ejemplo 08:00 o 08:30).");
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'location' => 'ubicación',
            'capacity' => 'capacidad',
            'opens_at' => 'hora de apertura',
            'closes_at' => 'hora de cierre',
            'max_duration_minutes' => 'duración máxima',
            'color' => 'color',
            'pickup_point' => 'punto de retiro',
            'amenities.*' => 'equipamiento',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Ya existe una sala con ese nombre.',
            'capacity.min' => 'La capacidad debe ser de al menos 1 persona.',
            'capacity.max' => 'La capacidad no puede superar las 200 personas.',
            'max_duration_minutes.multiple_of' => 'La duración máxima debe ser múltiplo de :value minutos.',
            'closes_at.after' => 'La hora de cierre debe ser posterior a la de apertura.',
        ];
    }

    // datos preparados para guardar en el modelo
    public function roomData(): array
    {
        return $this->safe()->only([
            'name', 'slug', 'description', 'location', 'capacity',
            'opens_at', 'closes_at', 'max_duration_minutes', 'is_active', 'color', 'pickup_point',
        ]);
    }
}
