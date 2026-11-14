<?php

namespace App\Actions\Waitlist;

use App\Models\Reservation;
use App\Models\WaitlistEntry;
use App\Notifications\WaitlistSlotFreed;
use Carbon\CarbonInterface;

// notifica a la lista de espera cuando se libera un horario
class NotifyWaitlist
{
    public function handle(Reservation $freed, ?CarbonInterface $from = null): int
    {
        $start = $from && $from->gt($freed->starts_at) ? $from : $freed->starts_at;

        if ($freed->ends_at->lte(now())) {
            return 0;
        }

        $entries = WaitlistEntry::query()
            ->pending()
            ->with('user')
            ->where('room_id', $freed->room_id)
            ->where('starts_at', '<', $freed->ends_at)
            ->where('ends_at', '>', $start)
            ->where('user_id', '!=', $freed->user_id)
            ->get();

        foreach ($entries as $entry) {
            $entry->user->notify(new WaitlistSlotFreed($entry, $freed->room));
            $entry->update(['notified_at' => now()]);
        }

        return $entries->count();
    }
}
