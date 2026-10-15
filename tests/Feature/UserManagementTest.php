<?php

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('admin can create users with either supported role and a hashed password', function (string $role) {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Pengguna baru',
            'email' => 'baru@example.test',
            'password' => 'rahasia-baru',
            'role' => $role,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'baru@example.test')->sole();

    expect($user->role)->toBe($role)
        ->and($user->password)->not->toBe('rahasia-baru')
        ->and(Hash::check('rahasia-baru', $user->password))->toBeTrue();
})->with(['admin', 'kasir']);

test('new user form defaults to cashier', function () {
    Livewire::test(CreateUser::class)
        ->assertFormSet(['role' => 'kasir'])
        ->fillForm([
            'name' => 'Kasir baru',
            'email' => 'kasir-baru@example.test',
            'password' => 'rahasia-kasir',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', ['email' => 'kasir-baru@example.test', 'role' => 'kasir']);
});

test('creating a user requires a password and a supported role', function (array $invalidData, array $errors) {
    Livewire::test(CreateUser::class)
        ->fillForm(array_replace([
            'name' => 'Pengguna tidak valid',
            'email' => 'tidak-valid@example.test',
            'password' => 'rahasia-pengguna',
            'role' => 'kasir',
        ], $invalidData))
        ->call('create')
        ->assertHasFormErrors($errors);

    $this->assertDatabaseMissing('users', ['email' => 'tidak-valid@example.test']);
})->with([
    'password kosong' => [['password' => ''], ['password' => 'required']],
    'peran kosong' => [['role' => null], ['role' => 'required']],
    'peran tidak dikenal' => [['role' => 'pemilik'], ['role' => 'in']],
]);

test('admin can change user details and role without replacing an empty password', function () {
    $user = User::factory()->create(['role' => 'kasir', 'password' => 'rahasia-lama']);
    $password = $user->password;

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormSet(['password' => null])
        ->fillForm(['name' => 'Nama diperbarui', 'role' => 'admin', 'password' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->name)->toBe('Nama diperbarui')
        ->and($user->role)->toBe('admin')
        ->and($user->password)->toBe($password)
        ->and(Hash::check('rahasia-lama', $user->password))->toBeTrue();
});

test('admin can replace a password and change an admin to cashier', function () {
    $user = User::factory()->create(['role' => 'admin', 'password' => 'rahasia-lama']);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['role' => 'kasir', 'password' => 'rahasia-pengganti'])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->role)->toBe('kasir')
        ->and(Hash::check('rahasia-pengganti', $user->password))->toBeTrue()
        ->and(Hash::check('rahasia-lama', $user->password))->toBeFalse();
});

test('editing rejects unsupported roles without modifying the account', function () {
    $user = User::factory()->create(['role' => 'kasir']);
    $password = $user->password;

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['role' => 'pemilik', 'password' => 'rahasia-pengganti'])
        ->call('save')
        ->assertHasFormErrors(['role' => 'in']);

    expect($user->fresh()->role)->toBe('kasir')
        ->and($user->fresh()->password)->toBe($password);
});

test('user emails must remain unique when creating or editing', function () {
    $existing = User::factory()->create(['role' => 'kasir']);
    $other = User::factory()->create(['role' => 'kasir']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Email sama',
            'email' => $existing->email,
            'password' => 'rahasia-baru',
            'role' => 'kasir',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);

    Livewire::test(EditUser::class, ['record' => $other->getRouteKey()])
        ->fillForm(['email' => $existing->email])
        ->call('save')
        ->assertHasFormErrors(['email' => 'unique']);

    expect($other->fresh()->email)->toBe($other->email);
});

test('cashier cannot access user pages through direct URLs or resource permissions', function () {
    $admin = auth()->user();
    $this->actingAs(User::factory()->create(['role' => 'kasir']));

    foreach ([
        UserResource::getUrl('index'),
        UserResource::getUrl('create'),
        UserResource::getUrl('edit', ['record' => $admin]),
    ] as $url) {
        $this->get($url)->assertForbidden();
    }

    expect(UserResource::shouldRegisterNavigation())->toBeFalse()
        ->and(UserResource::canViewAny())->toBeFalse()
        ->and(UserResource::canCreate())->toBeFalse()
        ->and(UserResource::canEdit($admin))->toBeFalse()
        ->and(UserResource::canDelete($admin))->toBeFalse();
});

test('a user form cannot save after the signed in admin loses access', function () {
    $admin = auth()->user();
    $user = User::factory()->create(['role' => 'kasir']);
    $component = Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['role' => 'admin']);

    $admin->update(['role' => 'kasir']);
    $this->actingAs($admin->fresh());

    $component->call('save')->assertForbidden();

    expect($user->fresh()->role)->toBe('kasir');
});

test('user role badges safely display unsupported legacy values', function () {
    $component = Livewire::test(ListUsers::class)->assertOk();
    $column = $component->instance()->getTable()->getColumn('role');

    expect($column->formatState('admin'))->toBe('Admin')
        ->and($column->formatState('kasir'))->toBe('Kasir')
        ->and($column->formatState('pemilik'))->toBe('Peran tidak dikenal')
        ->and($column->getColor('pemilik'))->toBe('gray')
        ->and($column->getColor(null))->toBe('gray');
});
