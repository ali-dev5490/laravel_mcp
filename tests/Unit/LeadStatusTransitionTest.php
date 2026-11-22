<?php

use App\Domain\Leads\LeadStatusTransition;
use App\Enums\LeadStatus;

test('qualified leads can move back to contacted or be marked lost', function () {
    expect(LeadStatusTransition::allows(LeadStatus::Qualified, LeadStatus::Lost))->toBeTrue()
        ->and(LeadStatusTransition::allows(LeadStatus::Qualified, LeadStatus::Contacted))->toBeTrue()
        ->and(LeadStatusTransition::allows(LeadStatus::Qualified, LeadStatus::Converted))->toBeFalse();
});

test('converted is explained as an order-only status', function () {
    expect(LeadStatusTransition::message(LeadStatus::Qualified, LeadStatus::Converted))
        ->toContain('place-order');
});

test('a converted lead cannot move', function () {
    expect(LeadStatusTransition::targets(LeadStatus::Converted))->toBe([])
        ->and(LeadStatusTransition::allows(LeadStatus::Converted, LeadStatus::Qualified))->toBeFalse();
});
