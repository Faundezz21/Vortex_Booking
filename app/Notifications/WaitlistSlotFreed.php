<?php

namespace App\Notifications;

use App\Models\Room;
use App\Models\WaitlistEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistSlotFreed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WaitlistEntry $entry, public Room $room)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tz = config('app.display_timezone');
        $start = $this->entry->starts_at->copy()->setTimezone($tz);

        return (new MailMessage)
            ->subject('Se liberó un horario en '.$this->room->name)
            ->greeting('¡Buenas noticias, '.$notifiable->name.'!')
            ->line('El bloque que estabas esperando quedó libre:')
            ->line('**Sala:** '.$this->room->name)
            ->line('**Horario:** '.$start->translatedFormat('l j \\d\\e F').', desde las '.$start->format('H:i'))
            ->line('Avisamos a todas las personas en espera al mismo tiempo: la primera que reserve se queda con el bloque.')
            ->action('Reservar ahora', route('rooms.show', ['room' => $this->room, 'fecha' => $start->toDateString(), 'hora' => $start->format('H:i')]));
    }
}
