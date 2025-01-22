<?php

namespace App\Console\Commands;

use App\Exceptions\Slapshot\LobbyLimitException;
use App\Services\Slapshot;
use Illuminate\Console\Command;

class CreateLobby extends Command
{
    protected $signature = 'slapshot:create-lobby';
    protected $description = 'Command description';

    public function handle()
    {
        try {
            $lobby = (new Slapshot())->createLobby(
                name: 'pug test',
                password: 'test',
                usePeriods: true,
                arena: 'Slapville_Jumbo',
                mercyRule: 0,
                teamSize: 4
            );

            $this->info("Lobby created, Slapshot ID: {$lobby->slapshot_id}");
        } catch (LobbyLimitException $e) {
            $this->error('Lobby limit reached.');
        }
    }
}
