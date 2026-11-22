<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueHarborToken extends Command
{
    protected $signature = 'harbor:token
        {email : Workspace member who will own the token}
        {--name=mcp-client : Name stored beside the token}';

    protected $description = 'Issue a Sanctum personal access token with the mcp:use ability';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->components->error("No user with email [{$email}]. Run php artisan migrate --seed first.");

            return self::FAILURE;
        }

        $token = $user->createToken((string) $this->option('name'), ['mcp:use']);

        $this->components->info('Sanctum token created. Store it now; Harborboard will not show it again.');
        $this->newLine();
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->line('Authorization: Bearer '.$token->plainTextToken);

        return self::SUCCESS;
    }
}
