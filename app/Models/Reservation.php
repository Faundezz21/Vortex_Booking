<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Carbon\CarbonInterface;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'checked_in_at',
        'released_at',
    ];

    protected $attributes = [
        'status' => 'confirmed',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'released_at' => 'datetime',
            'status' => ReservationStatus::class,
        ];
    }

    public function isConfirmed(): bool
    {
        return $this->status === ReservationStatus::Confirmed;
    }

    public function isNoShow(): bool
    {
        return $this->status === ReservationStatus::NoShow;
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    // calcula el inicio de la ventana habilitada para check in
    public function checkInOpensAt(): CarbonInterface
    {
        return $this->starts_at->copy()->subMinutes(config('booking.check_in.opens_minutes_before'));
    }

    public function checkInDeadline(): CarbonInterface
    {
        return $this->starts_at->copy()->addMinutes(config('booking.check_in.grace_minutes'));
    }

    public function canCheckInNow(): bool
    {
        return $this->isConfirmed()
            && ! $this->isCheckedIn()
            && now()->between($this->checkInOpensAt(), $this->checkInDeadline());
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return $this->status === ReservationStatus::Cancelled;
    }

    public function hasStarted(): bool
    {
        return $this->starts_at->isPast();
    }

    public function localStart(): CarbonInterface
    {
        return $this->starts_at->copy()->setTimezone(config('app.display_timezone'));
    }

    public function localEnd(): CarbonInterface
    {
        return $this->ends_at->copy()->setTimezone(config('app.display_timezone'));
    }

    public function timeRangeLabel(): string
    {
        return $this->localStart()->format('H:i').' – '.$this->localEnd()->format('H:i');
    }

    public function durationMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /** @param Builder<Reservation> $query */
    #[Scope]
    protected function confirmed(Builder $query): void
    {
        $query->where('status', ReservationStatus::Confirmed);
    }

    /** @param Builder<Reservation> $query */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('ends_at', '>', now());
    }

    // filtra reservas confirmadas que se cruzan con el rango horario
    #[Scope]
    protected function overlapping(Builder $query, int $roomId, CarbonInterface $start, CarbonInterface $end): void
    {
        $query->confirmed()
            ->where('room_id', $roomId)
            ->where('starts_at', '<', $end->copy()->utc())
            ->where('ends_at', '>', $start->copy()->utc());
    }
}
