<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// valida las reglas de negocio al crear una reserva
class StoreReservationRequest extends FormRequest
{
    private ?Room $room = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    // reglas de validacion para la peticion
    public function rules(): array
    {
        $slot = config('booking.slot_minutes');
        $tz = config('app.display_timezone');

        return [
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.now($tz)->toDateString(),
                'before_or_equal:'.now($tz)->addDays(config('booking.max_days_ahead'))->toDateString(),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', "min:$slot", "multiple_of:$slot"],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $room = $this->room();
                $start = $this->startsAt();
                $end = $this->endsAt();
                $slot = config('booking.slot_minutes');

                if ($start->minute % $slot !== 0) {
                    $validator->errors()->add('start_time', "La hora de inicio debe ser un bloque de $slot minutos.");

                    return;
                }

                if ($start->isPast()) {
                    $validator->errors()->add('start_time', 'No puedes reservar un horario que ya pasó.');

                    return;
                }

                if ($this->integer('duration_minutes') > $room->max_duration_minutes) {
                    $validator->errors()->add('duration_minutes', "Esta sala admite reservas de hasta {$room->max_duration_minutes} minutos.");

                    return;
                }

                if ($start->lt($room->opensAtOn($start)) || $end->gt($room->closesAtOn($start))) {
                    $validator->errors()->add('start_time', "La reserva debe estar dentro del horario de la sala ({$room->hoursLabel()}).");

                    return;
                }

                $active = $this->user()->reservations()->confirmed()->upcoming()->count();
                if ($active >= config('booking.max_active_per_user')) {
                    $validator->errors()->add('room_id', 'Ya tienes '.$active.' reservas activas, que es el máximo permitido. Cancela una para reservar otra.');

                    return;
                }

                if (Reservation::overlapping($room->id, $start, $end)->exists()) {
                    $validator->errors()->add('start_time', 'La sala ya está reservada en ese horario. Elige otro bloque.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'room_id' => 'sala',
            'date' => 'fecha',
            'start_time' => 'hora de inicio',
            'duration_minutes' => 'duración',
            'notes' => 'notas',
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.exists' => 'La sala seleccionada no está disponible.',
            'date.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'date.before_or_equal' => 'Solo se puede reservar con hasta '.config('booking.max_days_ahead').' días de anticipación.',
        ];
    }

    public function room(): Room
    {
        return $this->room ??= Room::findOrFail($this->integer('room_id'));
    }

    public function startsAt(): CarbonInterface
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->input('date').' '.$this->input('start_time'), config('app.display_timezone'))
            ->startOfMinute();
    }

    public function endsAt(): CarbonInterface
    {
        return $this->startsAt()->addMinutes($this->integer('duration_minutes'));
    }
}
