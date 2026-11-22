<?php

declare(strict_types=1);

namespace App\Mcp\Support;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Listing;
use App\Models\Order;

final class RecordPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function listingSummary(Listing $listing): array
    {
        return [
            'id' => $listing->id,
            'slug' => $listing->slug,
            'title' => $listing->title,
            'status' => $listing->status->value,
            'category' => $listing->category->value,
            'price' => $listing->price(),
            'currency' => $listing->currency,
            'location' => $listing->location,
            'summary' => $listing->summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listingDetail(Listing $listing): array
    {
        $counts = [];
        foreach (LeadStatus::cases() as $status) {
            $counts[$status->value] = $listing->leads()->where('status', $status)->count();
        }

        return [
            ...$this->listingSummary($listing),
            'description' => $listing->description,
            'published_at' => $listing->published_at?->toIso8601String(),
            'lead_counts' => $counts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function leadSummary(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company,
            'status' => $lead->status->value,
            'source' => $lead->source,
            'listing_id' => $lead->listing_id,
            'listing' => $lead->listing?->title,
            'listing_price' => $lead->listing?->price(),
            'listing_currency' => $lead->listing?->currency,
            'assignee' => $lead->assignee?->email,
            'message' => $lead->message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function leadDetail(Lead $lead): array
    {
        $lead->loadMissing(['listing', 'assignee', 'activities.user']);

        return [
            ...$this->leadSummary($lead),
            'activities' => $lead->activities
                ->sortBy('id')
                ->map(fn (LeadActivity $activity): array => [
                    'id' => $activity->id,
                    'type' => $activity->type->value,
                    'from_status' => $activity->from_status?->value,
                    'to_status' => $activity->to_status?->value,
                    'body' => $activity->body,
                    'by' => $activity->user?->email,
                    'at' => $activity->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function order(Order $order): array
    {
        $order->loadMissing(['listing', 'lead']);

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'amount' => $order->amount(),
            'currency' => $order->currency,
            'listing_id' => $order->listing_id,
            'listing' => $order->listing?->title,
            'lead_id' => $order->lead_id,
            'buyer' => $order->lead?->name,
            'notes' => $order->notes,
            'confirmed_at' => $order->confirmed_at->toIso8601String(),
        ];
    }
}
