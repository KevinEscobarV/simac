<?php

use App\Enums\QuorumType;
use App\Enums\Role;
use App\Models\Assembly;
use App\Models\City;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DemoEventSeeder;
use Illuminate\Support\Facades\Hash;

test('seeds a roll of 200 teachers across Casanare', function () {
    $this->seed(DemoEventSeeder::class);

    expect(Teacher::count())->toBe(200)
        ->and(Teacher::query()->distinct()->count('document_number'))->toBe(200)
        ->and(City::has('teachers')->count())->toBe(14)
        ->and(Teacher::query()->unionMembers()->count())->toBeGreaterThan(100);
});

test('seeds twenty desks that sign in with the demo password', function () {
    $this->seed(DemoEventSeeder::class);

    $desks = User::query()->where('email', 'like', 'mesa%@simac.test')->get();

    expect($desks)->toHaveCount(20)
        ->and($desks->every(fn (User $desk) => $desk->assignedRole() === Role::Registrar))->toBeTrue()
        ->and(Hash::check('password', $desks->first()->password))->toBeTrue();
});

test('leaves the assembly open with a quorum a demo can reach', function () {
    $this->seed(DemoEventSeeder::class);

    $assembly = Assembly::current();

    expect($assembly->attendances()->exists())->toBeFalse()
        ->and($assembly->quorum_type)->toBe(QuorumType::Count)
        ->and($assembly->quorum()->required())->toBe(30);
});
