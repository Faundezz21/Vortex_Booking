<?php

namespace Tests\Feature;

use App\Livewire\Calendar;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

// prueba visualizacion de calendario semanal y diario y feed ics
class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-16 08:00', 'America/Santiago'));
        $this->room = Room::factory()->create(['name' => 'Sala Grupal B2', 'slug' => 'sala-grupal-b2']);
        $this->user = User::factory()->create(['name' => 'Ana Pérez']);

        Reservation::factory()->for($this->user)->for($this->room)->between('2026-11-17 10:00', '2026-11-17 11:00')->create(['notes' => 'Repaso']);
        Reservation::factory()->for(User::factory()->create(['name' => 'Bruno Soto']))->for($this->room)->between('2026-11-18 15:00', '2026-11-18 16:30')->create();
    }

    #[Test]
    public function la_semana_muestra_mis_reservas_y_oculta_quien_reservo_las_ajenas(): void
    {
        Livewire::actingAs($this->user)
            ->test(Calendar::class)
            ->assertSee('16 – 22 de noviembre 2026')
            ->assertSee('Tu reserva')
            ->assertSee('Reservado')
            ->assertDontSee('Bruno Soto');
    }

    #[Test]
    public function el_administrador_ve_quien_hizo_cada_reserva(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Calendar::class)
            ->assertSee('Ana Pérez')
            ->assertSee('Bruno Soto');
    }

    #[Test]
    public function la_vista_dia_muestra_una_columna_por_sala_y_navega(): void
    {
        $other = Room::factory()->create(['name' => 'Sala Silenciosa E2', 'slug' => 'sala-silenciosa-e2']);

        Livewire::actingAs($this->user)
            ->test(Calendar::class)
            ->call('setView', 'dia')
            ->assertSee('Lunes 16 de noviembre 2026')
            ->assertSee($this->room->name)
            ->assertSee($other->name)
            ->call('next')
            ->assertSet('date', '2026-11-17')
            ->call('goToday')
            ->assertSet('date', '2026-11-16');
    }

    #[Test]
    public function los_espacios_libres_enlazan_a_la_reserva_con_la_hora_elegida(): void
    {
        $this->actingAs($this->user)
            ->get(route('calendar'))
            ->assertOk()
            ->assertSee(route('rooms.show', ['room' => $this->room, 'fecha' => '2026-11-17', 'hora' => '11:00']));

        $this->actingAs($this->user)
            ->get(route('rooms.show', ['room' => $this->room, 'fecha' => '2026-11-17', 'hora' => '11:00']))
            ->assertOk()
            ->assertSee('11:00 – 12:00');
    }

    #[Test]
    public function el_feed_ics_entrega_las_reservas_del_usuario(): void
    {
        $url = $this->user->calendarFeedUrl();

        $response = $this->get($url)->assertOk();

        $this->assertStringStartsWith('text/calendar', $response->headers->get('Content-Type'));
        $body = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $body);
        $this->assertSame(1, substr_count($body, 'BEGIN:VEVENT'), 'Solo deben aparecer las reservas del dueño del feed.');
        $this->assertStringContainsString('DTSTART:20261117T130000Z', $body);
        $this->assertStringContainsString('SUMMARY:B2 · Sala Grupal B2', $body);
        $this->assertStringContainsString("\r\n", $body);
    }

    #[Test]
    public function regenerar_el_enlace_invalida_el_anterior(): void
    {
        $old = $this->user->calendarFeedUrl();

        $this->actingAs($this->user)->post(route('settings.calendar.regenerate'))->assertSessionHas('status');

        $this->get($old)->assertNotFound();
        $this->get($this->user->fresh()->calendarFeedUrl())->assertOk();
    }

    #[Test]
    public function solo_el_dueno_o_un_admin_descarga_el_ics_de_una_reserva(): void
    {
        $reservation = $this->user->reservations()->first();

        $this->actingAs($this->user)->get(route('reservations.ics', $reservation))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="reserva-'.$reservation->id.'.ics"');

        $this->actingAs(User::factory()->create())->get(route('reservations.ics', $reservation))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('reservations.ics', $reservation))->assertOk();
    }
}
