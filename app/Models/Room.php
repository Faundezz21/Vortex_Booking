<?php

namespace App\Models;

use App\Enums\AmenityKind;
use App\Enums\ReservationStatus;
use App\Enums\RoomColor;
use Carbon\CarbonInterface;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'location',
        'capacity',
        'opens_at',
        'closes_at',
        'max_duration_minutes',
        'is_active',
        'color',
        'pickup_point',
    ];

    protected $attributes = [
        'color' => 'blue',
    ];

    protected $hidden = ['check_in_token'];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'max_duration_minutes' => 'integer',
            'is_active' => 'boolean',
            'color' => RoomColor::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            $room->check_in_token ??= Str::random(40);
        });
    }

    // regenera el token del qr para invalidar el codigo anterior
    public function regenerateCheckInToken(): void
    {
        $this->forceFill(['check_in_token' => Str::random(40)])->save();
    }

    public function checkInUrl(): string
    {
        return route('check-in.room', ['room' => $this, 'k' => $this->check_in_token]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<Amenity, $this> */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class)->ordered();
    }

    // comodidades y equipos instalados en la sala
    public function equipment(): Collection
    {
        return $this->amenities->where('kind', AmenityKind::Equipment)->values();
    }

    // elementos fisicos a retirar con la reserva
    public function pickupItems(): Collection
    {
        return $this->amenities->where('kind', AmenityKind::Pickup)->values();
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** @return HasMany<Reservation, $this> */
    public function confirmedReservations(): HasMany
    {
        return $this->reservations()->where('status', ReservationStatus::Confirmed);
    }

    // calcula la fecha y hora de apertura para el dia indicado
    public function opensAtOn(CarbonInterface $day): CarbonInterface
    {
        return $day->copy()->setTimezone(config('app.display_timezone'))->setTimeFromTimeString($this->opens_at);
    }

    // calcula la fecha y hora de cierre para el dia indicado
    public function closesAtOn(CarbonInterface $day): CarbonInterface
    {
        return $day->copy()->setTimezone(config('app.display_timezone'))->setTimeFromTimeString($this->closes_at);
    }

    // codigo identificador abreviado de la sala
    public function code(): string
    {
        $last = (string) str($this->name)->afterLast(' ');

        return preg_match('/^[A-Z]{1,2}\d{1,3}$/', $last)
            ? $last
            : (string) str($this->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    }

    public function hoursLabel(): string
    {
        return substr($this->opens_at, 0, 5).' – '.substr($this->closes_at, 0, 5);
    }

    /** @param Builder<Room> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
