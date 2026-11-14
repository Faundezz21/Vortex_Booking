<?php

namespace App\Livewire;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

// calendario semanal y diario de disponibilidad de salas
#[Title('Calendario')]
class Calendar extends Component
{
    #[Url(as: 'vista')]
    public string $view = 'semana';

    #[Url(as: 'fecha')]
    public string $date = '';

    #[Url(as: 'sala')]
    public string $room = '';

    public function mount(): void
    {
        $this->view = in_array($this->view, ['semana', 'dia'], true) ? $this->view : 'semana';
        $this->date = $this->parseDate($this->date)?->toDateString() ?? $this->today()->toDateString();

        if (! $this->rooms->firstWhere('slug', $this->room)) {
            $this->room = (string) $this->rooms->first()?->slug;
        }
    }

    public function setView(string $view): void
    {
        $this->view = $view === 'dia' ? 'dia' : 'semana';
    }

    public function selectRoom(string $slug): void
    {
        if ($this->rooms->firstWhere('slug', $slug)) {
            $this->room = $slug;
            unset($this->columns);
        }
    }

    public function previous(): void
    {
        $this->shift(-1);
    }

    public function next(): void
    {
        $this->shift(1);
    }

    public function goToday(): void
    {
        $this->date = $this->today()->toDateString();
    }

    public function openDay(string $date): void
    {
        $this->date = $this->parseDate($date)?->toDateString() ?? $this->date;
        $this->view = 'dia';
    }

    // salas activas ordenadas por nombre
    #[Computed]
    public function rooms(): Collection
    {
        return Room::active()->orderBy('name')->get();
    }

    #[Computed]
    public function currentRoom(): ?Room
    {
        return $this->rooms->firstWhere('slug', $this->room);
    }

    // limites de horario para calcular la grilla
    #[Computed]
    public function bounds(): array
    {
        $rooms = $this->view === 'semana' ? collect([$this->currentRoom])->filter() : $this->rooms;

        $open = $rooms->map(fn (Room $r) => (int) substr($r->opens_at, 0, 2))->min() ?? 8;
        $close = $rooms->map(fn (Room $r) => (int) ceil(((int) substr($r->closes_at, 0, 2) * 60 + (int) substr($r->closes_at, 3, 2)) / 60))->max() ?? 21;

        return [$open, max($close, $open + 1)];
    }

    // genera las columnas con eventos y bloques disponibles
    #[Computed]
    public function columns(): array
    {
        [$startHour, $endHour] = $this->bounds;
        $totalMinutes = ($endHour - $startHour) * 60;
        $day = $this->day();

        $specs = $this->view === 'semana'
            ? collect(range(0, 6))->map(fn ($i) => ['date' => $day->copy()->startOfWeek()->addDays($i), 'room' => $this->currentRoom])
            : $this->rooms->map(fn (Room $room) => ['date' => $day->copy(), 'room' => $room]);

        $specs = $specs->filter(fn ($s) => $s['room'] !== null)->values();
        if ($specs->isEmpty()) {
            return [];
        }

        $from = $specs->min(fn ($s) => $s['date'])->copy()->startOfDay();
        $to = $specs->max(fn ($s) => $s['date'])->copy()->endOfDay();

        $reservations = Reservation::confirmed()
            ->with('user:id,name')
            ->whereIn('room_id', $specs->pluck('room.id')->unique())
            ->where('starts_at', '<', $to->copy()->utc())
            ->where('ends_at', '>', $from->copy()->utc())
            ->get();

        $isAdmin = auth()->user()->isAdmin();
        $step = config('booking.slot_minutes');
        $limit = $this->today()->addDays(config('booking.max_days_ahead'))->endOfDay();

        return $specs->map(function (array $spec) use ($reservations, $startHour, $totalMinutes, $isAdmin, $step, $limit) {
            /** @var CarbonInterface $date */
            $date = $spec['date'];
            /** @var Room $room */
            $room = $spec['room'];
            $gridStart = $date->copy()->setTime($startHour, 0);
            $pos = fn (CarbonInterface $t) => max(0, min(100, $gridStart->diffInMinutes($t->copy()->setTimezone($gridStart->tz), false) / $totalMinutes * 100));

            $events = $reservations
                ->where('room_id', $room->id)
                ->filter(fn ($r) => $r->starts_at->lt($date->copy()->endOfDay()) && $r->ends_at->gt($date->copy()->startOfDay()))
                ->map(function (Reservation $r) use ($pos, $isAdmin) {
                    $mine = $r->user_id === auth()->id();

                    return [
                        'top' => $pos($r->starts_at),
                        'height' => max(2, $pos($r->ends_at) - $pos($r->starts_at)),
                        'time' => $r->timeRangeLabel(),
                        'title' => $mine ? 'Tu reserva' : ($isAdmin ? $r->user->name : 'Reservado'),
                        'mine' => $mine,
                        'checkedIn' => $r->isCheckedIn(),
                        'url' => $mine || $isAdmin ? route('reservations.show', $r) : null,
                    ];
                })->values()->all();

            // bloques libres reservables dentro del horario de la sala
            $slots = [];
            $open = $room->opensAtOn($date);
            $close = $room->closesAtOn($date);
            for ($t = $open->copy(); $t->lt($close); $t->addMinutes($step)) {
                $end = $t->copy()->addMinutes($step);
                $busy = $reservations->contains(fn ($r) => $r->room_id === $room->id && $r->starts_at->lt($end) && $r->ends_at->gt($t));
                if ($busy || $t->isPast() || $t->gt($limit)) {
                    continue;
                }
                $slots[] = [
                    'top' => $pos($t),
                    'height' => $pos($end) - $pos($t),
                    'label' => $t->format('H:i'),
                    'url' => route('rooms.show', ['room' => $room, 'fecha' => $date->toDateString(), 'hora' => $t->format('H:i')]),
                ];
            }

            return [
                'date' => $date,
                'room' => $room,
                'isToday' => $date->isSameDay($this->today()),
                'closedTop' => $pos($close),
                'openTop' => $pos($open),
                'events' => $events,
                'slots' => $slots,
            ];
        })->all();
    }

    #[Computed]
    public function rangeLabel(): string
    {
        $day = $this->day();

        if ($this->view === 'dia') {
            return ucfirst($day->translatedFormat('l j \d\e F Y'));
        }

        $start = $day->copy()->startOfWeek();
        $end = $start->copy()->addDays(6);

        return $start->month === $end->month
            ? $start->format('j').' – '.$end->translatedFormat('j \d\e F Y')
            : $start->translatedFormat('j M').' – '.$end->translatedFormat('j M Y');
    }

    // posicion vertical de la linea de hora actual
    #[Computed]
    public function nowTop(): ?float
    {
        [$startHour, $endHour] = $this->bounds;
        $now = now(config('app.display_timezone'));
        $minutes = ($now->hour - $startHour) * 60 + $now->minute;
        $total = ($endHour - $startHour) * 60;

        return $minutes >= 0 && $minutes <= $total ? $minutes / $total * 100 : null;
    }

    public function render()
    {
        return view('livewire.calendar');
    }

    private function shift(int $direction): void
    {
        $this->date = $this->day()->addDays($this->view === 'semana' ? 7 * $direction : $direction)->toDateString();
    }

    private function day(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->date, config('app.display_timezone'))->startOfDay();
    }

    private function today(): Carbon
    {
        return now(config('app.display_timezone'))->startOfDay();
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value, config('app.display_timezone'))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
