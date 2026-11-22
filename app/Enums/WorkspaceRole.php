<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Agent = 'agent';
    case Viewer = 'viewer';

    public function canWrite(): bool
    {
        return $this === self::Owner || $this === self::Agent;
    }
}
