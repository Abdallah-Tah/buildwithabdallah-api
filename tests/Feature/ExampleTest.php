<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response
        ->assertStatus(200)
        ->assertSee('Build With Abdallah')
        ->assertSee('One secure layer for')
        ->assertSee('api.buildwithabdallah.com')
        ->assertDontSee('APP_KEY');
});

test('the operations panel login is available', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Build With Abdallah');
});

test('a normal user cannot access the operations panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

test('an administrator can access the operations panel', function () {
    $user = User::factory()->admin()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
    expect(Gate::forUser($user)->allows('viewPulse'))->toBeTrue();
});
