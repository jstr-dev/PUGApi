<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateUser extends Command
{
    protected $signature = 'app:create-user';
    protected $description = 'Create a user and API key';

    public function handle()
    {
        $name = $this->ask('User name');
        $email = $this->ask('User email');

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->email_verified_at = now();
        $user->password = bcrypt(random_bytes(32));
        $user->save();

        $token = $user->createToken('api');

        $this->info('User created with API key: ' . $token->plainTextToken);
    }
}
