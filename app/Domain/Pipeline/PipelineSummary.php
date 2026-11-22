<?php

declare(strict_types=1);

namespace App\Domain\Pipeline;

use App\Enums\LeadStatus;
use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Money;

final class PipelineSummary
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $actor): array
    {
        $workspace = $actor->workspace;

        if (! $workspace instanceof Workspace) {
            $workspace = $actor->workspace()->firstOrFail();
        }

        $workspaceId = $workspace->id;

        $listings = [];
        foreach (ListingStatus::cases() as $status) {
            $listings[$status->value] = Listing::query()
                ->where('workspace_id', $workspaceId)
                ->where('status', $status)
                ->count();
        }

        $leads = [];
        foreach (LeadStatus::cases() as $status) {
            $leads[$status->value] = Lead::query()
                ->where('workspace_id', $workspaceId)
                ->where('status', $status)
                ->count();
        }

        $orders = [];
        $amounts = [];
        foreach (OrderStatus::cases() as $status) {
            $query = Order::query()
                ->where('workspace_id', $workspaceId)
                ->where('status', $status);

            $orders[$status->value] = (clone $query)->count();
            $amounts[$status->value] = Money::format((int) (clone $query)->sum('amount_cents'));
        }

        $attention = Lead::query()
            ->with('listing')
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', [LeadStatus::New, LeadStatus::Qualified])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Lead $lead): array {
                return [
                    'lead_id' => $lead->id,
                    'name' => $lead->name,
                    'status' => $lead->status->value,
                    'listing' => $lead->listing?->title,
                    'reason' => $lead->status === LeadStatus::New
                        ? 'New inquiry has not been contacted.'
                        : 'Qualified lead has no order yet.',
                ];
            })
            ->all();

        return [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'actor' => [
                'id' => $actor->id,
                'name' => $actor->name,
                'email' => $actor->email,
                'role' => $actor->role->value,
            ],
            'listings' => $listings,
            'leads' => $leads,
            'orders' => [
                'counts' => $orders,
                'amounts' => $amounts,
            ],
            'attention' => $attention,
        ];
    }
}
