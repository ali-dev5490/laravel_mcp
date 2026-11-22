<?php

declare(strict_types=1);

namespace App\Domain\Leads;

use App\Enums\LeadStatus;

final class LeadStatusTransition
{
    public static function allows(LeadStatus $from, LeadStatus $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::targets($from), true);
    }

    /**
     * @return list<LeadStatus>
     */
    public static function targets(LeadStatus $from): array
    {
        return match ($from) {
            LeadStatus::New => [LeadStatus::Contacted, LeadStatus::Qualified, LeadStatus::Lost],
            LeadStatus::Contacted => [LeadStatus::New, LeadStatus::Qualified, LeadStatus::Lost],
            LeadStatus::Qualified => [LeadStatus::Contacted, LeadStatus::Lost],
            LeadStatus::Lost => [LeadStatus::New],
            LeadStatus::Converted => [],
        };
    }

    public static function message(LeadStatus $from, LeadStatus $to): string
    {
        if ($to === LeadStatus::Converted) {
            return 'The converted status is reserved for place-order. Qualify the lead, preview the order, then call place-order with confirm=true.';
        }

        if ($from === LeadStatus::Converted) {
            return 'This lead was converted by an order and its status can no longer change.';
        }

        $allowed = array_map(fn (LeadStatus $status): string => $status->value, self::targets($from));

        return "A {$from->value} lead cannot move to {$to->value}. Allowed next statuses: ".implode(', ', $allowed).'.';
    }
}
