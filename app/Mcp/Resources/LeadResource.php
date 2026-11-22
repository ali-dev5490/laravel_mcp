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

#[Name('lead')]
#[Title('Lead')]
#[Description('JSON document for one lead in the signed-in workspace, including activity. URI template harbor://leads/{lead} accepts a numeric id.')]
#[MimeType('application/json')]
class LeadResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('harbor://leads/{lead}');
    }

    public function handle(Request $request, WorkspaceActor $actor, WorkspaceRecords $records, RecordPresenter $presenter): Response
    {
        $user = $actor->require($request);
        $key = $request->get('lead');

        if (! is_scalar($key) || ! ctype_digit((string) $key)) {
            return Response::error('The lead URI needs a numeric id, for example harbor://leads/1.');
        }

        $lead = $records->lead($user, (int) $key);

        return Response::text((string) json_encode(
            $presenter->leadDetail($lead),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }
}
