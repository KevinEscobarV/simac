<?php

use App\Enums\QuorumType;
use App\Support\Quorum;

test('the required members are rounded up', function (QuorumType $type, float $value, int $members, int $required) {
    expect(Quorum::requiredFor($type, $value, $members))->toBe($required);
})->with([
    'half of 13 is 7, not 6.5' => [QuorumType::Percentage, 50, 13, 7],
    'float noise does not add a member' => [QuorumType::Percentage, 70, 10, 7],
    'a fraction of a percentage' => [QuorumType::Percentage, 33.33, 9, 3],
    'a plain number' => [QuorumType::Count, 7, 13, 7],
]);

test('it tells how many are missing until it is met', function () {
    $quorum = new Quorum(QuorumType::Count, 7, unionMembers: 13, presentMembers: 5);

    expect($quorum->missing())->toBe(2)
        ->and($quorum->isMet())->toBeFalse()
        ->and($quorum->progress())->toBe(5 / 7);

    $met = new Quorum(QuorumType::Count, 7, unionMembers: 13, presentMembers: 9);

    expect($met->missing())->toBe(0)
        ->and($met->isMet())->toBeTrue()
        ->and($met->progress())->toBe(1.0);
});

test('a number above the roll is not capped, so it shows as unreachable', function () {
    $quorum = new Quorum(QuorumType::Count, 20, unionMembers: 13, presentMembers: 13);

    expect($quorum->required())->toBe(20)
        ->and($quorum->isReachable())->toBeFalse()
        ->and($quorum->isMet())->toBeFalse();
});
