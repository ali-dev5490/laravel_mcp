<?php

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('the http server requires a sanctum token', function () {
    $this->postJson('/mcp/harbor', rpc('tools/list'))->assertUnauthorized();
});

test('a token with the mcp ability can list and call tools', function () {
    $user = User::factory()->owner()->create([
        'email' => 'owner@harborboard.test',
    ]);
    $token = $user->createToken('test', ['mcp:use'])->plainTextToken;

    $listed = $this->withToken($token)->postJson('/mcp/harbor', rpc('tools/list'));

    $listed->assertOk();
    $names = collect($listed->json('result.tools'))->pluck('name');

    expect($names)->toContain('search-listings', 'pipeline-summary', 'archive-listing');

    $summary = $this->withToken($token)->postJson('/mcp/harbor', rpc('tools/call', [
        'name' => 'pipeline-summary',
        'arguments' => (object) [],
    ]));

    $summary->assertOk()
        ->assertJsonPath('result.structuredContent.actor.email', 'owner@harborboard.test');
});

test('tokens without the mcp ability are rejected', function () {
    $user = User::factory()->agent()->create();
    $token = $user->createToken('orders', ['orders:read'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/harbor', rpc('tools/list'))
        ->assertForbidden()
        ->assertJsonFragment(['message' => 'This token cannot use the Harborboard MCP server. Issue one with php artisan harbor:token {email}.']);
});

test('the http server is rate limited per user', function () {
    RateLimiter::for('mcp', fn (Request $request): Limit => Limit::perMinute(2)->by('test-suite'));

    $user = User::factory()->viewer()->create();
    $token = $user->createToken('test', ['mcp:use'])->plainTextToken;

    $this->withToken($token)->postJson('/mcp/harbor', rpc('tools/list'))->assertOk();
    $this->withToken($token)->postJson('/mcp/harbor', rpc('tools/list'))->assertOk();
    $this->withToken($token)->postJson('/mcp/harbor', rpc('tools/list'))->assertStatus(429);
});

/**
 * @param  array<string, mixed>  $params
 * @return array<string, mixed>
 */
function rpc(string $method, array $params = []): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => $method,
        'params' => $params === [] ? (object) [] : $params,
    ];
}
