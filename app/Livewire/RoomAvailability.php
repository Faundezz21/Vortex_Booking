<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\WaitlistEntry;
use App\Support\RoomSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

// grilla de disponibilidad de sala y gestion de reservas y lista de espera
class RoomAvailability extends Component
{
    public Room $room;

    #[Url(as: 'fecha')]
    public string $date = '';

    public ?string $startTime = null;

    public int $duration = 60;

    public string $notes = '';

    public ?string $notice = null;

    public function mount(Room $room): void
    {
        $tz = config('app.display_timezone');

        $old = old('date');
        $this->date = $this->validDate($old ?: $this->date) ?? now($tz)->toDateString();
        $this->startTime = old('start_time');
        $this->notes = (string) old('notes', '');
        $this->duration = (int) (old('duration_minutes') ?: min(60, $room->max_duration_minutes));

        // selecciona el bloque cuando viene por parametro de url
        $hora = request()->query('hora');
        if (! $this->startTime && is_string($hora) && ($this->timeSlots->firstWhere('time', $hora)['state'] ?? null) === 'free') {
            $this->selectSlot($hora);
        }

        // recupera errores de sesion tras la redireccion
        if ($errors = session('errors')) {
            $this->setErrorBag($errors->getBag('default'));
        }
    }

    // agrega al usuario a la lista de espera para el bloque seleccionado
    public function joinWaitlist(string $time): void
    {
        $slot = $this->timeSlots->firstWhere('time', $time);
        if (($slot['state'] ?? null) !== 'busy') {
            return;
        }

        $user = auth()->user();
        if ($user->waitlistEntries()->pending()->count() >= 5) {
            $this->notice = 'Puedes esperar hasta 5 bloques a la vez. Sal de alguna lista para unirte a otra.';

            return;
        }

        $start = $this->slotStart($time);
        WaitlistEntry::firstOrCreate(
            ['user_id' => $user->id, 'room_id' => $this->room->id, 'starts_at' => $start->copy()->utc()],
            ['ends_at' => $start->copy()->addMinutes(config('booking.slot_minutes'))->utc()],
        );

        $this->notice = "Te avisaremos por correo si el bloque de las {$time} se libera.";
        unset($this->waitingTimes);
    }

    public function leaveWaitlist(string $time): void
    {
        auth()->user()->waitlistEntries()
            ->where('room_id', $this->room->id)
            ->where('starts_at', $this->slotStart($time)->utc())
            ->delete();

        $this->notice = "Saliste de la lista de espera de las {$time}.";
        unset($this->waitingTimes);
    }

    // retorna los horarios donde el usuario espera disponibilidad
    #[Computed]
    public function waitingTimes(): array
    {
        $tz = config('app.display_timezone');
        $day = Carbon::createFromFormat('Y-m-d', $this->date, $tz)->startOfDay();

        return auth()->user()->waitlistEntries()->pending()
            ->where('room_id', $this->room->id)
            ->whereBetween('starts_at', [$day->copy()->utc(), $day->copy()->endOfDay()->utc()])
            ->pluck('starts_at')
            ->map(fn ($t) => $t->copy()->setTimezone($tz)->format('H:i'))
            ->all();
    }

    private function slotStart(string $time): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->date.' '.$time, config('app.display_timezone'));
    }

    public function updatedDate(string $value): void
    {
        $this->date = $this->validDate($value) ?? now(config('app.display_timezone'))->toDateString();
        $this->startTime = null;
        $this->notice = null;
        $this->resetErrorBag();
    }

    public function shiftDay(int $days): void
    {
        $this->updatedDate(Carbon::parse($this->date)->addDays($days)->toDateString());
    }

    public function selectSlot(string $time): void
    {
        $this->resetErrorBag();
        $this->startTime = $time;
        $max = $this->maxDurationFrom($time);
        if ($this->duration > $max) {
            $this->duration = $max;
        }
    }

    // lista de bloques horarios con su estado de disponibilidad
    #[Computed]
    public function timeSlots(): Collection
    {
        $day = Carbon::createFromFormat('Y-m-d', $this->date, config('app.display_timezone'));

        return RoomSchedule::day($this->room, $day, auth()->id());
    }

    // calcula opciones de duracion segun bloques libres consecutivos
    #[Computed]
    public function durationOptions(): array
    {
        $step = config('booking.slot_minutes');
        $max = $this->startTime ? $this->maxDurationFrom($this->startTime) : $this->room->max_duration_minutes;

        return $max >= $step ? range($step, $max, $step) : [];
    }

    #[Computed]
    public function freeCount(): int
    {
        return $this->timeSlots->where('state', 'free')->count();
    }

    public function render()
    {
        $tz = config('app.display_timezone');
        $today = now($tz)->startOfDay();

        return view('livewire.room-availability', [
            'day' => Carbon::createFromFormat('Y-m-d', $this->date, $tz),
            'days' => collect(range(0, config('booking.max_days_ahead')))->map(fn (int $i) => $today->copy()->addDays($i)),
        ]);
    }

    private function maxDurationFrom(string $time): int
    {
        $step = config('booking.slot_minutes');
        $slots = $this->timeSlots->values();
        $index = $slots->search(fn ($s) => $s['time'] === $time);
        if ($index === false) {
            return 0;
        }

        $minutes = 0;
        for ($i = $index; $i < $slots->count() && $slots[$i]['state'] === 'free'; $i++) {
            $minutes += $step;
        }

        return min($minutes, $this->room->max_duration_minutes);
    }

    private function validDate(?string $value): ?string
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $tz = config('app.display_timezone');
        $date = Carbon::createFromFormat('Y-m-d', $value, $tz)->startOfDay();
        $today = now($tz)->startOfDay();

        if ($date->lt($today) || $date->gt($today->copy()->addDays(config('booking.max_days_ahead')))) {
            return null;
        }

        return $date->toDateString();
    }
}
