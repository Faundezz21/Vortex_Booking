<?php

namespace Tests\Feature\Reservations;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationRulesTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(Carbon::parse('2026-11-16 09:10', 'America/Santiago'));

        $this->room = Room::factory()->create(['opens_at' => '08:00', 'closes_at' => '20:00', 'max_duration_minutes' => 120]);
        $this->user = User::factory()->create();
    }

    private function reserve(array $overrides = [])
    {
        return $this->actingAs($this->user)->post(route('reservations.store'), array_merge([
            'room_id' => $this->room->id,
            'date' => '2026-11-17',
            'start_time' => '10:00',
            'duration_minutes' => 60,
            'notes' => 'Repaso',
        ], $overrides));
    }

    #[Test]
    public function crea_la_reserva_en_utc_y_envia_el_correo_de_confirmacion(): void
    {
        $this->reserve()->assertSessionHasNoErrors()->assertRedirect();

        $reservation = Reservation::sole();
        $this->assertTrue($reservation->user->is($this->user));
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        // conversion de hora local a formato utc
        $this->assertSame('2026-11-17 13:00:00', $reservation->getRawOriginal('starts_at'));
        $this->assertSame('2026-11-17 14:00:00', $reservation->getRawOriginal('ends_at'));

        Notification::assertSentTo($this->user, ReservationConfirmed::class, fn ($n) => $n->reservation->is($reservation));
    }

    #[Test]
    public function el_invitado_no_puede_reservar(): void
    {
        $this->post(route('reservations.store'), ['room_id' => $this->room->id])->assertRedirect(route('login'));
        $this->assertDatabaseCount('reservations', 0);
    }

    #[Test]
    public function rechaza_un_horario_que_ya_paso(): void
    {
        $this->reserve(['date' => '2026-11-16', 'start_time' => '09:00'])
            ->assertSessionHasErrors(['start_time' => 'No puedes reservar un horario que ya pasó.']);
    }

    #[Test]
    public function rechaza_fechas_pasadas_o_demasiado_lejanas(): void
    {
        $this->reserve(['date' => '2026-11-15'])->assertSessionHasErrors('date');
        $this->reserve(['date' => '2026-12-31'])->assertSessionHasErrors('date');
    }

    #[Test]
    public function rechaza_reservas_fuera_del_horario_de_la_sala(): void
    {
        $this->reserve(['start_time' => '07:30'])->assertSessionHasErrors('start_time');
        $this->reserve(['start_time' => '19:30', 'duration_minutes' => 60])->assertSessionHasErrors('start_time');
    }

    #[Test]
    public function rechaza_duraciones_mayores_al_maximo_de_la_sala(): void
    {
        $this->reserve(['duration_minutes' => 150])
            ->assertSessionHasErrors(['duration_minutes' => 'Esta sala admite reservas de hasta 120 minutos.']);
    }

    #[Test]
    public function exige_bloques_de_30_minutos(): void
    {
        $this->reserve(['start_time' => '10:15'])->assertSessionHasErrors('start_time');
        $this->reserve(['duration_minutes' => 45])->assertSessionHasErrors('duration_minutes');
    }

    #[Test]
    public function rechaza_salas_inactivas_o_eliminadas(): void
    {
        $this->room->update(['is_active' => false]);
        $this->reserve()->assertSessionHasErrors('room_id');

        $this->room->update(['is_active' => true]);
        $this->room->delete();
        $this->reserve()->assertSessionHasErrors('room_id');
    }

    #[Test]
    public function limita_las_reservas_activas_por_usuario(): void
    {
        config(['booking.max_active_per_user' => 2]);
        Reservation::factory()->for($this->user)->for($this->room)->between('2026-11-18 10:00', '2026-11-18 11:00')->create();
        Reservation::factory()->for($this->user)->for($this->room)->between('2026-11-19 10:00', '2026-11-19 11:00')->create();

        $this->reserve()->assertSessionHasErrors('room_id');

        // las reservas canceladas no restan cupos
        Reservation::query()->first()->update(['status' => ReservationStatus::Cancelled]);
        $this->reserve()->assertSessionHasNoErrors();
    }

    #[Test]
    public function valida_campos_obligatorios_y_formatos(): void
    {
        $this->actingAs($this->user)
            ->post(route('reservations.store'), ['date' => '17-11-2026', 'start_time' => '10h'])
            ->assertSessionHasErrors(['room_id', 'date', 'start_time', 'duration_minutes']);
    }

    #[Test]
    public function el_usuario_cancela_su_reserva_y_recibe_un_correo(): void
    {
        $reservation = Reservation::factory()->for($this->user)->for($this->room)->between('2026-11-17 10:00', '2026-11-17 11:00')->create();

        $this->actingAs($this->user)
            ->patch(route('reservations.cancel', $reservation))
            ->assertSessionHas('status');

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Cancelled, $reservation->status);
        $this->assertSame($this->user->id, $reservation->cancelled_by);
        $this->assertNotNull($reservation->cancelled_at);

        Notification::assertSentTo($this->user, ReservationCancelled::class);
    }

    #[Test]
    public function el_usuario_no_puede_cancelar_reservas_ajenas(): void
    {
        $reservation = Reservation::factory()->for($this->room)->between('2026-11-17 10:00', '2026-11-17 11:00')->create();

        $this->actingAs($this->user)->patch(route('reservations.cancel', $reservation))->assertNotFound();

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        Notification::assertNothingSent();
    }

    #[Test]
    public function el_usuario_no_puede_cancelar_una_reserva_que_ya_comenzo(): void
    {
        $reservation = Reservation::factory()->for($this->user)->for($this->room)->between('2026-11-16 09:00', '2026-11-16 10:00')->create();

        $this->actingAs($this->user)
            ->patch(route('reservations.cancel', $reservation))
            ->assertSessionHas('error', 'No puedes cancelar una reserva que ya comenzó.');

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
    }

    #[Test]
    public function el_administrador_puede_cancelar_cualquier_reserva_y_se_notifica_al_dueno(): void
    {
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->for($this->user)->for($this->room)->between('2026-11-17 10:00', '2026-11-17 11:00')->create();

        $this->actingAs($admin)->patch(route('reservations.cancel', $reservation))->assertSessionHas('status');

        $this->assertSame($admin->id, $reservation->fresh()->cancelled_by);
        Notification::assertSentTo($this->user, ReservationCancelled::class, function ($notification) {
            return str_contains(implode(' ', $notification->toMail($this->user)->introLines), 'Un administrador canceló tu reserva');
        });
    }

    #[Test]
    public function la_vista_de_disponibilidad_marca_los_bloques_ocupados(): void
    {
        Reservation::factory()->for($this->room)->between('2026-11-17 10:00', '2026-11-17 11:00')->create();

        $this->actingAs($this->user)
            ->get(route('rooms.show', $this->room).'?fecha=2026-11-17')
            ->assertOk()
            ->assertSee($this->room->name)
            ->assertSeeInOrder(['10:00', 'Ocupado'], false)
            ->assertSee('Confirmar reserva');
    }
}
