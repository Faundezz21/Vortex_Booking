<?php

namespace Tests\Feature\Reservations;

use App\Enums\ReservationStatus;
use App\Livewire\RoomAvailability;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Notifications\ReservationReleased;
use App\Notifications\WaitlistSlotFreed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

// prueba check in con qr liberacion de inasistencias y lista de espera
class CheckInAndNoShowTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private User $user;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(Carbon::parse('2026-11-16 09:55', 'America/Santiago'));

        $this->room = Room::factory()->create();
        $this->user = User::factory()->create();
        $this->reservation = Reservation::factory()->for($this->user)->for($this->room)
            ->between('2026-11-16 10:00', '2026-11-16 12:00')->create();
    }

    private function checkInUrl(?string $token = null): string
    {
        return route('check-in.store', ['room' => $this->room, 'k' => $token ?? $this->room->check_in_token]);
    }

    #[Test]
    public function registra_la_llegada_dentro_de_la_ventana(): void
    {
        $this->actingAs($this->user)->get(route('check-in.room', ['room' => $this->room, 'k' => $this->room->check_in_token]))
            ->assertOk()
            ->assertSee('Registrar mi llegada');

        $this->actingAs($this->user)->post($this->checkInUrl())
            ->assertRedirect(route('reservations.show', $this->reservation))
            ->assertSessionHas('status');

        $this->assertNotNull($this->reservation->fresh()->checked_in_at);
    }

    #[Test]
    public function un_qr_invalido_o_reemplazado_no_sirve(): void
    {
        $oldToken = $this->room->check_in_token;
        $this->room->regenerateCheckInToken();

        $this->actingAs($this->user)->post($this->checkInUrl($oldToken))->assertNotFound();
        $this->actingAs($this->user)->get(route('check-in.room', ['room' => $this->room, 'k' => 'falso']))->assertNotFound();

        $this->assertNull($this->reservation->fresh()->checked_in_at);
    }

    #[Test]
    public function no_permite_check_in_fuera_de_la_ventana(): void
    {
        // fuera de la ventana previa de apertura
        $this->travelTo(Carbon::parse('2026-11-16 09:40', 'America/Santiago'));
        $this->actingAs($this->user)->post($this->checkInUrl())->assertSessionHas('error');

        // superado el plazo de gracia de quince minutos
        $this->travelTo(Carbon::parse('2026-11-16 10:20', 'America/Santiago'));
        $this->actingAs($this->user)->post($this->checkInUrl())->assertSessionHas('error');

        $this->assertNull($this->reservation->fresh()->checked_in_at);
    }

    #[Test]
    public function el_qr_solo_registra_la_reserva_de_quien_lo_escanea(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)->post($this->checkInUrl())->assertSessionHas('error');

        $this->assertNull($this->reservation->fresh()->checked_in_at);
    }

    #[Test]
    public function libera_la_reserva_sin_check_in_y_avisa_al_dueno_y_a_la_lista_de_espera(): void
    {
        $waiting = User::factory()->create();
        WaitlistEntry::create([
            'user_id' => $waiting->id,
            'room_id' => $this->room->id,
            'starts_at' => Carbon::parse('2026-11-16 11:00', 'America/Santiago')->utc(),
            'ends_at' => Carbon::parse('2026-11-16 11:30', 'America/Santiago')->utc(),
        ]);

        // dentro del plazo de gracia la reserva permanece confirmada
        $this->travelTo(Carbon::parse('2026-11-16 10:14', 'America/Santiago'));
        $this->artisan('reservations:release-no-shows')->assertSuccessful();
        $this->assertSame(ReservationStatus::Confirmed, $this->reservation->fresh()->status);

        $this->travelTo(Carbon::parse('2026-11-16 10:16', 'America/Santiago'));
        $this->artisan('reservations:release-no-shows')->expectsOutput('1 reserva liberada.');

        $released = $this->reservation->fresh();
        $this->assertSame(ReservationStatus::NoShow, $released->status);
        $this->assertNotNull($released->released_at);

        Notification::assertSentTo($this->user, ReservationReleased::class);
        Notification::assertSentTo($waiting, WaitlistSlotFreed::class);
        $this->assertNotNull(WaitlistEntry::sole()->notified_at);
    }

    #[Test]
    public function no_libera_reservas_con_check_in(): void
    {
        $this->reservation->update(['checked_in_at' => now()]);
        $this->travelTo(Carbon::parse('2026-11-16 10:30', 'America/Santiago'));

        $this->artisan('reservations:release-no-shows')->expectsOutput('0 reservas liberadas.');

        $this->assertSame(ReservationStatus::Confirmed, $this->reservation->fresh()->status);
        Notification::assertNothingSent();
    }

    #[Test]
    public function el_bloque_liberado_se_puede_volver_a_reservar(): void
    {
        $this->travelTo(Carbon::parse('2026-11-16 10:16', 'America/Santiago'));
        $this->artisan('reservations:release-no-shows');

        // permite volver a reservar el horario liberado
        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), [
                'room_id' => $this->room->id,
                'date' => '2026-11-16',
                'start_time' => '10:30',
                'duration_minutes' => 90,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Reservation::confirmed()->count());
    }

    #[Test]
    public function cancelar_avisa_solo_a_quienes_esperaban_un_horario_que_se_cruza(): void
    {
        [$a, $b] = User::factory(2)->create();
        $entry = fn (User $u, string $start) => WaitlistEntry::create([
            'user_id' => $u->id,
            'room_id' => $this->room->id,
            'starts_at' => Carbon::parse("2026-11-16 $start", 'America/Santiago')->utc(),
            'ends_at' => Carbon::parse("2026-11-16 $start", 'America/Santiago')->addMinutes(30)->utc(),
        ]);
        $entry($a, '11:30'); // horario dentro de la cancelacion
        $entry($b, '12:00'); // horario posterior que no colisiona

        $this->actingAs($this->user)->patch(route('reservations.cancel', $this->reservation));

        Notification::assertSentTo($a, WaitlistSlotFreed::class);
        Notification::assertNotSentTo($b, WaitlistSlotFreed::class);
    }

    #[Test]
    public function se_puede_anotar_en_lista_de_espera_solo_en_bloques_ocupados(): void
    {
        $other = User::factory()->create();

        Livewire::actingAs($other)
            ->test(RoomAvailability::class, ['room' => $this->room])
            ->set('date', '2026-11-16')
            ->call('joinWaitlist', '10:30') // bloque ocupado
            ->assertSet('notice', 'Te avisaremos por correo si el bloque de las 10:30 se libera.')
            ->call('joinWaitlist', '14:00'); // bloque libre que se omite

        $this->assertSame(1, WaitlistEntry::count());
        $this->assertSame('2026-11-16 13:30:00', WaitlistEntry::sole()->getRawOriginal('starts_at'));

        Livewire::actingAs($other)
            ->test(RoomAvailability::class, ['room' => $this->room])
            ->set('date', '2026-11-16')
            ->call('leaveWaitlist', '10:30');

        $this->assertSame(0, WaitlistEntry::count());
    }
}
