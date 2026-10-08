<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('guests are redirected away from protected pages', function () {
    $this->get('/dashboard')->assertRedirect('/');
    $this->get('/crud')->assertRedirect('/');
});

test('the login screen renders as an inertia page', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
});

test('a user can log in with their username and password', function () {
    User::factory()->create([
        'username' => 'admin',
        'role' => 'admin',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'username' => 'admin',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticated();
});

test('invalid credentials return a validation error', function () {
    User::factory()->create([
        'username' => 'admin',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'username' => 'admin',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('the dashboard renders for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
});

test('the users index lists users through inertia', function () {
    User::factory()->count(3)->create();

    $this->actingAs(User::factory()->create())
        ->get('/crud')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Crud/Index')
            ->has('users.data', 4)
        );
});

test('the create, edit and show pages render through inertia', function () {
    $target = User::factory()->create(['username' => 'target']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/crud/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Crud/Create'));

    $this->actingAs($admin)->get("/crud/{$target->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Crud/Edit')
            ->where('user.id', $target->id)
        );

    $this->actingAs($admin)->get("/crud/{$target->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Crud/Show')
            ->where('user.username', 'target')
        );
});

test('an admin can create, update and delete a user', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/crud', [
        'name' => 'New Person',
        'username' => 'newperson',
        'email' => 'new@example.com',
        'role' => 'user',
        'password' => 'secret123',
    ])->assertRedirect('/crud');

    $this->assertDatabaseHas('users', ['username' => 'newperson', 'email' => 'new@example.com']);

    $created = User::where('username', 'newperson')->firstOrFail();

    $this->actingAs($admin)->put("/crud/{$created->id}", [
        'name' => 'Renamed Person',
        'username' => 'newperson',
        'email' => 'new@example.com',
        'role' => 'user',
    ])->assertRedirect('/crud');

    $this->assertDatabaseHas('users', ['id' => $created->id, 'name' => 'Renamed Person']);

    $this->actingAs($admin)->delete("/crud/{$created->id}")->assertRedirect('/crud');

    $this->assertDatabaseMissing('users', ['id' => $created->id]);
});

test('creating a user validates required fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post('/crud', [])
        ->assertSessionHasErrors(['name', 'username', 'email', 'role', 'password']);
});

test('a user can log out', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

test('unknown routes render the inertia 404 page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('Errors/NotFound'));
});
