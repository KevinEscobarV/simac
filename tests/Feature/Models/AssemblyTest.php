<?php

use App\Enums\QuorumType;
use App\Models\Assembly;
use App\Models\Attendance;
use App\Models\Teacher;

test('the quorum counts only the union members present right now', function () {
    $assembly = Assembly::factory()->withQuorum(QuorumType::Percentage, 50)->create();
    Attendance::factory()->for($assembly)->count(2)->create();
    Attendance::factory()->for($assembly)->checkedOut()->create();
    Attendance::factory()->for($assembly)->for(Teacher::factory()->nonMember())->create();
    Teacher::factory()->count(2)->create();

    $quorum = $assembly->quorum();

    expect($quorum->unionMembers)->toBe(5)
        ->and($quorum->presentMembers)->toBe(2)
        ->and($quorum->required())->toBe(3);
});

test('an assembly without a quorum has none', function () {
    expect(Assembly::factory()->create()->quorum())->toBeNull();
});

test('the current assembly is the open one', function () {
    Assembly::factory()->closed()->create();
    $open = Assembly::factory()->create();

    expect(Assembly::current()?->is($open))->toBeTrue();
});
