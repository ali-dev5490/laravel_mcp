<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $ready = Schema::hasTable('workspaces');
        $workspace = $ready ? Workspace::query()->where('slug', 'northline')->first() : null;

        return view('welcome', [
            'workspace' => $workspace,
            'counts' => [
                'listings' => $workspace instanceof Workspace ? Listing::query()->where('workspace_id', $workspace->id)->count() : 0,
                'leads' => $workspace instanceof Workspace ? Lead::query()->where('workspace_id', $workspace->id)->count() : 0,
                'orders' => $workspace instanceof Workspace ? Order::query()->where('workspace_id', $workspace->id)->count() : 0,
            ],
        ]);
    }
}
