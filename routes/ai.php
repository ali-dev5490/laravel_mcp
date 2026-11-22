<?php

use App\Mcp\Servers\HarborServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('harbor', HarborServer::class);

Mcp::web('/mcp/harbor', HarborServer::class)
    ->middleware(['auth:sanctum', 'mcp.ability', 'throttle:mcp']);
