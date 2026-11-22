<?php

declare(strict_types=1);

namespace App\Domain\Orders;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Mcp\Support\RecordPresenter;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OrderPlacement
{
    public function __construct(private readonly RecordPresenter $presenter) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(User $actor, Lead $lead, ?string $notes): array
    {
        $listing = $this->assertReady($lead);

        return [
            'committed' => false,
            'requires_confirmation' => true,
            'message' => 'No order was written. Show this preview to the user, then call place-order again with confirm=true to commit it.',
            'preview' => $this->previewPayload($lead, $listing, $notes),
            'order' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function commit(User $actor, Lead $lead, ?string $notes): array
    {
        $order = DB::transaction(function () use ($actor, $lead, $notes): Order {
            $lockedLead = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            $listing = $this->assertReady($lockedLead);
            $lockedListing = Listing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();

            if ($lockedListing->status !== ListingStatus::Published) {
                throw ValidationException::withMessages([
                    'lead_id' => "The listing \"{$lockedListing->title}\" is {$lockedListing->status->value} and cannot be sold.",
                ]);
            }

            $sequence = (int) Order::query()
                ->where('workspace_id', $actor->workspace_id)
                ->lockForUpdate()
                ->count() + 1;

            $order = Order::query()->create([
                'workspace_id' => $actor->workspace_id,
                'listing_id' => $lockedListing->id,
                'lead_id' => $lockedLead->id,
                'placed_by' => $actor->id,
                'number' => sprintf('HB-%d-%04d', $actor->workspace_id, $sequence),
                'amount_cents' => $lockedListing->price_cents,
                'currency' => $lockedListing->currency,
                'status' => OrderStatus::Pending,
                'notes' => $notes,
                'confirmed_at' => now(),
            ]);

            $from = $lockedLead->status;
            $lockedLead->status = LeadStatus::Converted;
            $lockedLead->save();

            $lockedListing->status = ListingStatus::Sold;
            $lockedListing->save();

            $lockedLead->activities()->create([
                'user_id' => $actor->id,
                'type' => LeadActivityType::Converted,
                'from_status' => $from,
                'to_status' => LeadStatus::Converted,
                'body' => "Order {$order->number} placed for {$lockedListing->price()} {$lockedListing->currency}.",
            ]);

            return $order;
        });

        $order->load(['listing', 'lead']);

        return [
            'committed' => true,
            'requires_confirmation' => false,
            'message' => "Order {$order->number} was created. The lead is converted and the listing is sold.",
            'preview' => null,
            'order' => $this->presenter->order($order),
        ];
    }

    private function assertReady(Lead $lead): Listing
    {
        if ($lead->status !== LeadStatus::Qualified) {
            throw ValidationException::withMessages([
                'lead_id' => "Only a qualified lead can become an order. This lead is {$lead->status->value}. Use update-lead-status first.",
            ]);
        }

        $listing = $lead->listing;

        if (! $listing instanceof Listing) {
            throw ValidationException::withMessages([
                'lead_id' => 'This lead is not attached to a listing, so there is nothing to sell.',
            ]);
        }

        if ($listing->workspace_id !== $lead->workspace_id) {
            throw ValidationException::withMessages([
                'lead_id' => 'The listing on this lead is outside the workspace. Refusing to sell it.',
            ]);
        }

        if ($listing->status !== ListingStatus::Published) {
            throw ValidationException::withMessages([
                'lead_id' => "The listing \"{$listing->title}\" is {$listing->status->value} and cannot be sold. Only published listings can be ordered.",
            ]);
        }

        $existing = Order::query()
            ->where('lead_id', $lead->id)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->first();

        if ($existing instanceof Order) {
            throw ValidationException::withMessages([
                'lead_id' => "This lead already has order {$existing->number}. Refusing to create a second order.",
            ]);
        }

        return $listing;
    }

    /**
     * @return array<string, mixed>
     */
    private function previewPayload(Lead $lead, Listing $listing, ?string $notes): array
    {
        return [
            'lead_id' => $lead->id,
            'buyer' => $lead->name,
            'buyer_email' => $lead->email,
            'listing_id' => $listing->id,
            'listing' => $listing->title,
            'amount' => $listing->price(),
            'currency' => $listing->currency,
            'resulting_lead_status' => LeadStatus::Converted->value,
            'resulting_listing_status' => ListingStatus::Sold->value,
            'notes' => $notes,
        ];
    }
}
