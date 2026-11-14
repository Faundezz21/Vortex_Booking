<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ReservationConfirmed;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

// prueba equipamiento de salas y elementos a retirar con la reserva
class AmenitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AmenitySeeder::class);
    }

    private function ids(string ...$slugs): array
    {
        return Amenity::whereIn('slug', $slugs)->pluck('id')->all();
    }

    private function roomData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sala Grupal B9',
            'location' => 'Biblioteca, piso 2',
            'capacity' => 6,
            'opens_at' => '08:00',
            'closes_at' => '20:00',
            'max_duration_minutes' => 120,
            'is_active' => '1',
        ], $overrides);
    }

    #[Test]
    public function el_administrador_asigna_equipamiento_y_punto_de_retiro(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.rooms.store'), $this->roomData([
                'amenities' => $this->ids('aire-acondicionado', 'proyector', 'llave', 'control-aire'),
                'pickup_point' => 'Mesón de préstamo, piso 1',
            ]))
            ->assertSessionHasNoErrors();

        $room = Room::where('slug', 'sala-grupal-b9')->sole();
        $this->assertEqualsCanonicalizing(['aire-acondicionado', 'proyector'], $room->equipment()->pluck('slug')->all());
        $this->assertEqualsCanonicalizing(['llave', 'control-aire'], $room->pickupItems()->pluck('slug')->all());
        $this->assertSame('Mesón de préstamo, piso 1', $room->pickup_point);
    }

    #[Test]
    public function al_editar_se_reemplaza_el_equipamiento(): void
    {
        $room = Room::factory()->create(['name' => 'Sala B9', 'slug' => 'sala-b9']);
        $room->amenities()->sync($this->ids('proyector', 'wifi'));

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.rooms.update', $room), $this->roomData(['name' => 'Sala B9', 'amenities' => $this->ids('pizarra')]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['pizarra'], $room->fresh()->amenities->pluck('slug')->all());
    }

    #[Test]
    public function si_se_entregan_elementos_hay_que_indicar_donde_se_retiran(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.rooms.store'), $this->roomData(['amenities' => $this->ids('llave')]))
            ->assertSessionHasErrors('pickup_point');

        // no requiere punto de retiro si solo tiene equipamiento
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.rooms.store'), $this->roomData(['amenities' => $this->ids('wifi')]))
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function rechaza_equipamiento_inexistente(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.rooms.store'), $this->roomData(['amenities' => [999]]))
            ->assertSessionHasErrors('amenities.0');
    }

    #[Test]
    public function el_catalogo_filtra_por_equipamiento(): void
    {
        $withProjector = Room::factory()->create(['name' => 'Sala Proyector P1', 'slug' => 'sala-proyector-p1']);
        $withProjector->amenities()->sync($this->ids('proyector'));
        Room::factory()->create(['name' => 'Sala Simple S1', 'slug' => 'sala-simple-s1']);

        $this->actingAs(User::factory()->create())
            ->get(route('rooms.index', ['con' => 'proyector']))
            ->assertOk()
            ->assertSee('Sala Proyector P1')
            ->assertDontSee('Sala Simple S1');
    }

    #[Test]
    public function la_sala_y_el_correo_indican_que_retirar_y_donde(): void
    {
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-11-16 08:00', 'America/Santiago'));

        $room = Room::factory()->create(['pickup_point' => 'Conserjería, piso 1']);
        $room->amenities()->sync($this->ids('aire-acondicionado', 'llave', 'control-aire'));
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('rooms.show', $room))
            ->assertOk()
            ->assertSee('Aire acondicionado')
            ->assertSee('Retira antes de entrar')
            ->assertSee('Control del aire acondicionado')
            ->assertSee('Conserjería, piso 1');

        $this->actingAs($user)->post(route('reservations.store'), [
            'room_id' => $room->id,
            'date' => '2026-11-16',
            'start_time' => '10:00',
            'duration_minutes' => 60,
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ReservationConfirmed::class, function ($notification) use ($user) {
            $text = implode(' ', $notification->toMail($user)->introLines);

            return str_contains($text, 'Llave de la sala, Control del aire acondicionado')
                && str_contains($text, 'Conserjería, piso 1');
        });

        $this->actingAs($user)->get(route('reservations.show', Reservation::sole()))
            ->assertSee('Retira antes de entrar');
    }
}
