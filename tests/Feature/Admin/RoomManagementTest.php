<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoomManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sala de Prueba 1',
            'location' => 'Biblioteca, piso 1',
            'description' => 'Sala para tests',
            'capacity' => 6,
            'opens_at' => '08:00',
            'closes_at' => '20:00',
            'max_duration_minutes' => 120,
            'is_active' => '1',
        ], $overrides);
    }

    #[Test]
    public function un_usuario_normal_no_accede_a_la_administracion(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $this->actingAs($user)->get(route('admin.rooms.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.rooms.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.rooms.store'), $this->validData())->assertForbidden();
        $this->actingAs($user)->put(route('admin.rooms.update', $room), $this->validData())->assertForbidden();
        $this->actingAs($user)->delete(route('admin.rooms.destroy', $room))->assertForbidden();
        $this->actingAs($user)->get(route('admin.reservations.index'))->assertForbidden();

        $this->assertDatabaseCount('rooms', 1);
    }

    #[Test]
    public function el_administrador_crea_una_sala(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.rooms.store'), $this->validData())
            ->assertRedirect(route('admin.rooms.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('rooms', ['name' => 'Sala de Prueba 1', 'slug' => 'sala-de-prueba-1', 'capacity' => 6, 'is_active' => true]);
    }

    #[Test]
    public function el_administrador_edita_y_desactiva_una_sala(): void
    {
        $room = Room::factory()->create(['name' => 'Original', 'slug' => 'original']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.rooms.update', $room), $this->validData(['name' => 'Renombrada', 'is_active' => '0']))
            ->assertRedirect(route('admin.rooms.index'));

        $room->refresh();
        $this->assertSame('Renombrada', $room->name);
        $this->assertSame('renombrada', $room->slug);
        $this->assertFalse($room->is_active);
    }

    #[Test]
    public function valida_los_datos_de_la_sala_en_el_servidor(): void
    {
        Room::factory()->create(['name' => 'Duplicada', 'slug' => 'duplicada']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.rooms.store'), $this->validData([
                'name' => 'Duplicada',
                'capacity' => 0,
                'opens_at' => '09:15',
                'closes_at' => '08:00',
                'max_duration_minutes' => 45,
            ]))
            ->assertSessionHasErrors(['slug', 'capacity', 'closes_at', 'max_duration_minutes']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.rooms.store'), $this->validData(['opens_at' => '08:15']))
            ->assertSessionHasErrors('opens_at');

        $this->assertDatabaseCount('rooms', 1);
    }

    #[Test]
    public function elimina_con_soft_delete_y_conserva_el_historial(): void
    {
        $room = Room::factory()->create();
        $past = Reservation::factory()->for($room)->create([
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDays(2)->addHour(),
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.rooms.destroy', $room))
            ->assertRedirect(route('admin.rooms.index'));

        $this->assertSoftDeleted($room);
        $this->assertNotNull($past->fresh()->room, 'La reserva histórica debe seguir mostrando su sala.');
    }

    #[Test]
    public function no_elimina_una_sala_con_reservas_futuras(): void
    {
        Notification::fake();
        $room = Room::factory()->create();
        Reservation::factory()->for($room)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.rooms.index'))
            ->delete(route('admin.rooms.destroy', $room))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($room);
    }

    #[Test]
    public function el_registro_publico_siempre_crea_usuarios_con_rol_usuario(): void
    {
        Volt::test('auth.register')
            ->set('name', 'Nueva Persona')
            ->set('email', 'nueva@example.com')
            ->set('password', 'password-segura')
            ->set('password_confirmation', 'password-segura')
            ->call('register');

        $this->assertSame(Role::User, User::where('email', 'nueva@example.com')->sole()->role);

        // el rol no es asignable masivamente para evitar escalamiento de permisos
        $user = User::create(['name' => 'Intruso', 'email' => 'intruso@example.com', 'password' => 'x', 'role' => 'admin']);
        $this->assertSame(Role::User, $user->fresh()->role);
    }
}
