<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('qualification-playbook')]
#[Title('Qualification playbook')]
#[Description('How Harborboard decides a lead is qualified, and the rule that place-order must be previewed before it is confirmed.')]
#[Uri('harbor://playbook/qualification')]
#[MimeType('text/markdown')]
#[Audience([Role::Assistant, Role::User])]
#[Priority(0.9)]
class QualificationPlaybookResource extends Resource
{
    public function handle(Request $request): Response
    {
        $path = resource_path('harbor/qualification-playbook.md');

        if (! is_file($path)) {
            return Response::error('The qualification playbook is missing from the application.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return Response::error('The qualification playbook could not be read.');
        }

        return Response::text($contents);
    }
}
