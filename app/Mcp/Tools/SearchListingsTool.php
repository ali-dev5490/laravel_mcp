<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ListingCategory;
use App\Enums\ListingStatus;
use App\Mcp\Concerns\InteractsWithWorkspace;
use App\Mcp\Support\RecordPresenter;
use App\Models\Listing;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('search-listings')]
#[Title('Search listings')]
#[Description('Search the signed-in workspace\'s classified listings by text, status, category, and price. Prices are major currency units, such as 700 for $700.00. Read-only.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class SearchListingsTool extends Tool
{
    use InteractsWithWorkspace;

    public function handle(Request $request, RecordPresenter $presenter): Response|ResponseFactory
    {
        $actor = $this->actor($request);
        $denied = $this->denied('viewAny', Listing::class);

        if ($denied instanceof Response) {
            return $denied;
        }

        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:'.implode(',', array_column(ListingStatus::cases(), 'value'))],
            'category' => ['nullable', 'in:'.implode(',', array_column(ListingCategory::cases(), 'value'))],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ], [
            'status.in' => 'Status must be draft, published, sold, or archived.',
            'category.in' => 'Category must be furniture, lighting, storage, or decor.',
            'limit.max' => 'Ask for at most 25 listings per call. Use offset to page.',
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $offset = (int) ($validated['offset'] ?? 0);

        $listings = Listing::query()->where('workspace_id', $actor->workspace_id);

        if (isset($validated['query']) && trim((string) $validated['query']) !== '') {
            $term = '%'.$this->plain((string) $validated['query']).'%';
            $listings->where(function ($query) use ($term): void {
                $query->where('title', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('location', 'like', $term);
            });
        }

        if (isset($validated['status'])) {
            $listings->where('status', $validated['status']);
        }

        if (isset($validated['category'])) {
            $listings->where('category', $validated['category']);
        }

        if (isset($validated['min_price'])) {
            $listings->where('price_cents', '>=', Money::toCents($validated['min_price']));
        }

        if (isset($validated['max_price'])) {
            $listings->where('price_cents', '<=', Money::toCents($validated['max_price']));
        }

        $total = (clone $listings)->count();

        match ($validated['sort'] ?? 'newest') {
            'price_asc' => $listings->orderBy('price_cents')->orderBy('id'),
            'price_desc' => $listings->orderByDesc('price_cents')->orderByDesc('id'),
            default => $listings->orderByDesc('id'),
        };

        $rows = $listings->skip($offset)->take($limit)->get();

        return Response::structured([
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'listings' => $rows->map(fn (Listing $listing): array => $presenter->listingSummary($listing))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Words matched against title, summary, description, and location.')->max(120),
            'status' => $schema->string()->enum(ListingStatus::class)->description('Filter by listing status.'),
            'category' => $schema->string()->enum(ListingCategory::class)->description('Filter by category.'),
            'min_price' => $schema->number()->description('Minimum price in major units, such as 100 for $100.00.')->min(0),
            'max_price' => $schema->number()->description('Maximum price in major units, such as 700 for $700.00.')->min(0),
            'sort' => $schema->string()->enum(['newest', 'price_asc', 'price_desc'])->description('Sort order. Defaults to newest.'),
            'limit' => $schema->integer()->description('Page size. Defaults to 10, maximum 25.')->min(1)->max(25),
            'offset' => $schema->integer()->description('Number of matches to skip. Defaults to 0.')->min(0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total' => $schema->integer()->description('Matches before paging.')->required(),
            'limit' => $schema->integer()->required(),
            'offset' => $schema->integer()->required(),
            'listings' => $schema->array()->items($schema->object([
                'id' => $schema->integer()->required(),
                'slug' => $schema->string()->required(),
                'title' => $schema->string()->required(),
                'status' => $schema->string()->required(),
                'category' => $schema->string()->required(),
                'price' => $schema->string()->description('Decimal string, such as 840.00.')->required(),
                'currency' => $schema->string()->required(),
                'location' => $schema->string()->nullable(),
                'summary' => $schema->string()->required(),
            ]))->required(),
        ];
    }

    private function plain(string $value): string
    {
        return trim(str_replace(['%', '_'], '', $value));
    }
}
