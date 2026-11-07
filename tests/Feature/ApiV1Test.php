<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the api ping endpoint is public', function () {
    $this->getJson('/api/v1/ping')
        ->assertOk()
        ->assertJson(['status' => 'ok', 'version' => 'v1']);
});

test('the api me endpoint requires a sanctum token', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

test('a sanctum token authenticates the player', function () {
    $user = User::factory()->create(['username' => 'api-user']);
    $token = $user->createToken('mobile', ['read'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.username', 'api-user')
        ->assertJsonPath('data.roles', []);
});

test('unknown api routes return json 404 responses', function () {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertJson(['message' => 'Not found.']);
});
