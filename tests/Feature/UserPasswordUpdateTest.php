<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserPasswordUpdateTest extends TestCase
{
    private string $originalHash;

    protected function setUp(): void
    {
        parent::setUp();

        // Base aislada: estas pruebas nunca migran ni modifican la base configurada.
        config(['database.default' => 'password_test', 'database.connections.password_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->unsignedInteger('role_id');
            $table->string('password');
            $table->timestamps();
        });
        $this->originalHash = Hash::make('Original1!');
        DB::table('users')->insert([
            'id' => 1, 'name' => 'Usuario', 'email' => 'usuario@example.com',
            'role_id' => 2, 'password' => $this->originalHash,
        ]);
    }

    public function test_empty_null_or_omitted_password_preserves_existing_hash(): void
    {
        foreach ([[], ['password' => ''], ['password' => null]] as $password) {
            $response = $this->updateUser($password);
            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame($this->originalHash, User::findOrFail(1)->password);
            $this->assertSame('ACTUALIZADO', User::findOrFail(1)->name);
        }
    }

    public function test_valid_password_is_hashed_and_hidden_in_response_data(): void
    {
        $response = $this->updateUser(['password' => 'NuevaClave12!']);
        $this->assertSame(200, $response->getStatusCode());
        $user = User::findOrFail(1);
        $this->assertNotSame($this->originalHash, $user->password);
        $this->assertTrue(Hash::check('NuevaClave12!', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_invalid_password_is_rejected_without_updating_user(): void
    {
        foreach (['corta', 'sinmayuscula1!', 'SINMINUSCULA1!', 'SinNumero!', 'SinSimbolo12', 'MuyLargaClave1234!'] as $password) {
            $response = $this->updateUser(['password' => $password]);
            $this->assertSame(400, $response->getStatusCode());
            $this->assertArrayHasKey('password', $response->getData(true));
            $this->assertSame($this->originalHash, User::findOrFail(1)->password);
            $this->assertSame('Usuario', User::findOrFail(1)->name);
        }
    }

    private function updateUser(array $password)
    {
        $request = Request::create('/api/users/1', 'PUT', array_merge([
            'name' => 'Actualizado', 'email' => 'usuario@example.com', 'role_id' => 2,
        ], $password));

        return app(UserController::class)->update($request, 1);
    }
}
