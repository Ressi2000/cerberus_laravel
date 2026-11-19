<?php

namespace Tests\Feature\Usuarios;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FotoPerfilTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function un_analista_cambia_su_foto_de_perfil(): void
    {
        Storage::fake('public');
        Role::firstOrCreate(['name' => 'Analista', 'guard_name' => 'web']);
        $empresa = Empresa::create(['nombre' => 'Empresa A']);
        $user = User::factory()->create(['empresa_id' => $empresa->id, 'empresa_activa_id' => $empresa->id]);
        $user->assignRole('Analista');
        $user->empresasAsignadas()->attach($empresa->id);

        $r = $this->actingAs($user)->from(route('profile.edit'))
            ->put(route('profile.photo.update'), ['foto' => UploadedFile::fake()->image('yo.jpg', 400, 400)]);

        $r->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->foto);
        Storage::disk('public')->assertExists($user->fresh()->foto);
    }

    /** @test */
    public function una_foto_grande_se_achica_y_un_archivo_que_no_es_imagen_se_rechaza_en_espanol(): void
    {
        Storage::fake('public');
        Role::firstOrCreate(['name' => 'Analista', 'guard_name' => 'web']);
        $empresa = Empresa::create(['nombre' => 'Empresa B']);
        $user = User::factory()->create(['empresa_id' => $empresa->id, 'empresa_activa_id' => $empresa->id]);
        $user->assignRole('Analista');
        $user->empresasAsignadas()->attach($empresa->id);

        $this->actingAs($user)->from(route('profile.edit'))
            ->put(route('profile.photo.update'), ['foto' => UploadedFile::fake()->image('grande.jpg', 3000, 2000)->size(4500)])
            ->assertSessionHasNoErrors();

        [$ancho, $alto] = getimagesizefromstring(Storage::disk('public')->get($user->fresh()->foto));
        $this->assertSame(600, max($ancho, $alto), 'Se guarda achicada.');

        $this->actingAs($user)->from(route('profile.edit'))
            ->put(route('profile.photo.update'), ['foto' => UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors(['foto' => 'El archivo tiene que ser una imagen (JPG, PNG…).']);
    }
}
