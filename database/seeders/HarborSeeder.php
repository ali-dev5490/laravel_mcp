<?php

namespace Database\Seeders;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Enums\ListingCategory;
use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Enums\WorkspaceRole;
use App\Models\Lead;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class HarborSeeder extends Seeder
{
    public function run(): void
    {
        if (Workspace::query()->where('slug', 'northline')->exists()) {
            return;
        }

        $workspace = Workspace::query()->create([
            'name' => 'Northline Studio',
            'slug' => 'northline',
        ]);

        $password = (string) config('harbor.demo_password');

        $owner = $this->member($workspace, 'Avery Chen', (string) config('harbor.users.owner'), WorkspaceRole::Owner, $password);
        $agent = $this->member($workspace, 'Jordan Hale', (string) config('harbor.users.agent'), WorkspaceRole::Agent, $password);
        $this->member($workspace, 'Sam Okonkwo', (string) config('harbor.users.viewer'), WorkspaceRole::Viewer, $password);

        $desk = $this->listing($workspace, $owner, [
            'title' => 'Oak writing desk',
            'slug' => 'oak-writing-desk',
            'summary' => 'Solid white-oak desk with a leather writing inset and two drawers.',
            'description' => 'Built in the Northline shop. The top is 60 inches, the inset is vegetable-tanned leather, and the drawers are soft-close. Local pickup in Portland or crated freight.',
            'category' => ListingCategory::Furniture,
            'price_cents' => 84_000,
            'status' => ListingStatus::Published,
            'location' => 'Portland, OR',
            'published_at' => now()->subDays(18),
        ]);

        $chair = $this->listing($workspace, $owner, [
            'title' => 'Linen lounge chair',
            'slug' => 'linen-lounge-chair',
            'summary' => 'Low lounge chair in oatmeal linen with a walnut frame.',
            'description' => 'One chair, currently on the studio floor. The cushion is removable. The frame is black-stained walnut.',
            'category' => ListingCategory::Furniture,
            'price_cents' => 62_000,
            'status' => ListingStatus::Published,
            'location' => 'Portland, OR',
            'published_at' => now()->subDays(12),
        ]);

        $lamp = $this->listing($workspace, $owner, [
            'title' => 'Brass arc lamp',
            'slug' => 'brass-arc-lamp',
            'summary' => 'Unlacquered brass arc lamp with a linen shade.',
            'description' => 'The brass will darken. The shade is 14 inches. It ships in two boxes from Seattle.',
            'category' => ListingCategory::Lighting,
            'price_cents' => 31_000,
            'status' => ListingStatus::Published,
            'location' => 'Seattle, WA',
            'published_at' => now()->subDays(9),
        ]);

        $this->listing($workspace, $owner, [
            'title' => 'Walnut media console',
            'slug' => 'walnut-media-console',
            'summary' => 'Draft console, 72 inches, not listed publicly yet.',
            'description' => 'Still in the finishing room. Do not quote this as available.',
            'category' => ListingCategory::Storage,
            'price_cents' => 129_000,
            'status' => ListingStatus::Draft,
            'location' => 'Portland, OR',
            'published_at' => null,
        ]);

        $ceramics = $this->listing($workspace, $owner, [
            'title' => 'Ceramic table lamps',
            'slug' => 'ceramic-table-lamps',
            'summary' => 'A pair of glazed ceramic lamps. Sold.',
            'description' => 'Sold to a returning client. Kept on file so the order history stays attached.',
            'category' => ListingCategory::Lighting,
            'price_cents' => 18_000,
            'status' => ListingStatus::Sold,
            'location' => 'Portland, OR',
            'published_at' => now()->subDays(40),
        ]);

        $this->listing($workspace, $owner, [
            'title' => 'Marble side table',
            'slug' => 'marble-side-table',
            'summary' => 'Archived after a chip was found in the top.',
            'description' => 'Honore marble top on a steel base. Archived rather than discounted.',
            'category' => ListingCategory::Decor,
            'price_cents' => 45_000,
            'status' => ListingStatus::Archived,
            'location' => 'Seattle, WA',
            'published_at' => now()->subDays(30),
        ]);

        $maya = $this->lead($workspace, $desk, $agent, [
            'name' => 'Maya Ellis',
            'email' => 'maya.ellis@example.com',
            'phone' => '503-555-0148',
            'company' => 'Ellis Studio',
            'message' => 'I want the oak writing desk for a client studio in Portland. The recorded price works if you can hold it through Friday.',
            'status' => LeadStatus::Qualified,
        ]);
        $this->activity($maya, $owner, LeadActivityType::Created, null, LeadStatus::New, 'Inquiry arrived from the studio site.');
        $this->activity($maya, $agent, LeadActivityType::StatusChange, LeadStatus::New, LeadStatus::Qualified, 'Buyer confirmed the recorded price and a Friday hold.');

        $chris = $this->lead($workspace, $chair, null, [
            'name' => 'Chris Patel',
            'email' => 'chris.patel@example.com',
            'message' => 'Is the linen lounge chair still on the floor? I can visit Thursday afternoon.',
            'status' => LeadStatus::New,
        ]);
        $this->activity($chris, $owner, LeadActivityType::Created, null, LeadStatus::New, 'Inquiry arrived from the studio site.');

        $elena = $this->lead($workspace, $lamp, $owner, [
            'name' => 'Elena Rossi',
            'email' => 'elena.rossi@example.com',
            'company' => 'Rossi Interiors',
            'message' => 'Please send the shade dimensions for the brass arc lamp and whether the brass is lacquered.',
            'status' => LeadStatus::Contacted,
        ]);
        $this->activity($elena, $owner, LeadActivityType::Created, null, LeadStatus::New, 'Inquiry arrived from the studio site.');
        $this->activity($elena, $owner, LeadActivityType::StatusChange, LeadStatus::New, LeadStatus::Contacted, 'Sent shade size and noted that the brass is unlacquered.');

        $noah = $this->lead($workspace, $desk, $agent, [
            'name' => 'Noah Kim',
            'email' => 'noah.kim@example.com',
            'message' => 'The oak desk is beautiful, but the budget for this room stops at $500.',
            'status' => LeadStatus::Lost,
        ]);
        $this->activity($noah, $agent, LeadActivityType::Created, null, LeadStatus::New, 'Inquiry arrived from the studio site.');
        $this->activity($noah, $agent, LeadActivityType::StatusChange, LeadStatus::New, LeadStatus::Lost, 'Budget is below the recorded price.');

        $priya = $this->lead($workspace, $ceramics, $owner, [
            'name' => 'Priya Shah',
            'email' => 'priya.shah@example.com',
            'company' => 'Shah House',
            'message' => 'I will take the pair of ceramic lamps at the recorded price.',
            'status' => LeadStatus::Converted,
        ]);
        $this->activity($priya, $owner, LeadActivityType::Created, null, LeadStatus::New, 'Inquiry arrived from the studio site.');
        $this->activity($priya, $owner, LeadActivityType::Converted, LeadStatus::Qualified, LeadStatus::Converted, 'Order placed for the recorded price.');

        Order::query()->create([
            'workspace_id' => $workspace->id,
            'listing_id' => $ceramics->id,
            'lead_id' => $priya->id,
            'placed_by' => $owner->id,
            'number' => sprintf('HB-%d-%04d', $workspace->id, 1),
            'amount_cents' => $ceramics->price_cents,
            'currency' => 'USD',
            'status' => OrderStatus::Fulfilled,
            'notes' => 'Picked up from the Portland studio.',
            'confirmed_at' => now()->subDays(6),
        ]);
    }

    private function member(Workspace $workspace, string $name, string $email, WorkspaceRole $role, string $password): User
    {
        return User::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function listing(Workspace $workspace, User $author, array $attributes): Listing
    {
        return Listing::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $author->id,
            'currency' => 'USD',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function lead(Workspace $workspace, Listing $listing, ?User $assignee, array $attributes): Lead
    {
        return Lead::query()->create([
            'workspace_id' => $workspace->id,
            'listing_id' => $listing->id,
            'assignee_id' => $assignee?->id,
            'source' => 'studio-site',
            ...$attributes,
        ]);
    }

    private function activity(Lead $lead, User $user, LeadActivityType $type, ?LeadStatus $from, ?LeadStatus $to, string $body): void
    {
        $lead->activities()->create([
            'user_id' => $user->id,
            'type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'body' => $body,
        ]);
    }
}
