<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// registro de usuarios en lista de espera para bloques ocupados
class WaitlistEntry extends Model
{
    protected $fillable = ['user_id', 'room_id', 'starts_at', 'ends_at', 'notified_at'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // filtra entradas pendientes que aun no vencen
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('notified_at')->where('starts_at', '>', now());
    }
}
