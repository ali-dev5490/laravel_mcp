<?php

declare(strict_types=1);

namespace App\Domain\Listings;

use App\Enums\LeadStatus;
use App\Enums\ListingStatus;
use App\Mcp\Support\RecordPresenter;
use App\Models\Listing;
use App\Models\User;

final class ListingArchiver
{
    public function __construct(private readonly RecordPresenter $presenter) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(Listing $listing, string $reason): array
    {
        if ($listing->status === ListingStatus::Archived) {
            return $this->payload(
                committed: false,
                requiresConfirmation: false,
                alreadyArchived: true,
                message: 'This listing is already archived. No change is required.',
                listing: $listing,
                reason: $reason,
            );
        }

        return $this->payload(
            committed: false,
            requiresConfirmation: true,
            alreadyArchived: false,
            message: 'No change was written. Show this preview to the user, then call archive-listing again with confirm=true to archive it.',
            listing: $listing,
            reason: $reason,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function commit(User $actor, Listing $listing, string $reason): array
    {
        if ($listing->status === ListingStatus::Archived) {
            return $this->payload(
                committed: true,
                requiresConfirmation: false,
                alreadyArchived: true,
                message: 'This listing was already archived. No further change was made.',
                listing: $listing,
                reason: $reason,
            );
        }

        $listing->status = ListingStatus::Archived;
        $listing->save();

        return $this->payload(
            committed: true,
            requiresConfirmation: false,
            alreadyArchived: false,
            message: "Archived \"{$listing->title}\" as {$actor->name}. Reason: {$reason}",
            listing: $listing->refresh(),
            reason: $reason,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        bool $committed,
        bool $requiresConfirmation,
        bool $alreadyArchived,
        string $message,
        Listing $listing,
        string $reason,
    ): array {
        return [
            'committed' => $committed,
            'requires_confirmation' => $requiresConfirmation,
            'already_archived' => $alreadyArchived,
            'message' => $message,
            'reason' => $reason,
            'open_leads' => $listing->leads()
                ->whereNotIn('status', [LeadStatus::Lost, LeadStatus::Converted])
                ->count(),
            'listing' => $this->presenter->listingSummary($listing),
        ];
    }
}
