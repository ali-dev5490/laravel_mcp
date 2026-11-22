<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Mcp\Support\RecordPresenter;
use App\Mcp\Support\WorkspaceActor;
use App\Mcp\Support\WorkspaceRecords;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('listing')]
#[Title('Listing')]
#[Description('JSON document for one listing in the signed-in workspace. URI template harbor://listings/{listing} accepts an id or a slug.')]
#[MimeType('application/json')]
class ListingResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('harbor://listings/{listing}');
    }

    public function handle(Request $request, WorkspaceActor $actor, WorkspaceRecords $records, RecordPresenter $presenter): Response
    {
        $user = $actor->require($request);
        $key = $request->get('listing');

        if (! is_string($key) && ! is_int($key)) {
            return Response::error('The listing URI needs an id or slug, for example harbor://listings/oak-writing-desk.');
        }

        $listing = $records->listing($user, (string) $key);

        return Response::text((string) json_encode(
            $presenter->listingDetail($listing),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}
