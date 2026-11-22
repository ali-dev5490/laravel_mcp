<?php

use App\Models\User;

test('harbor token issues an mcp ability and rejects unknown users', function () {
    $user = User::factory()->owner()->create([
        'email' => 'owner@harborboard.test',
    ]);

    $this->artisan('harbor:token', ['email' => 'missing@harborboard.test'])
        ->assertFailed();

    $this->artisan('harbor:token', ['email' => $user->email])
        ->assertOk()
        ->expectsOutputToContain('Authorization: Bearer');

    $token = $user->tokens()->first();

    expect($token)->not->toBeNull()
        ->and($token->abilities)->toContain('mcp:use');
});
