<?php

namespace App\Console\Commands;

use App\Exceptions\Slapshot\LobbyLimitException;
use App\Models\GameLobby;
use App\Models\Player;
use App\Services\GameService;
use App\Services\Slapshot;
use Illuminate\Console\Command;

class CreateLobby extends Command
{
    protected $signature = 'slapshot:create-lobby';
    protected $description = 'Command description';

    public function handle()
    {
        try {
            $lobbyId = (new Slapshot())->createLobby(
                name: 'Hiya!',
                password: 'test',
                usePeriods: true,
                arena: 'Slapville_Jumbo',
                mercyRule: 2,
                teamSize: 4
            );

            $lobby = new GameLobby();
            $lobby->slapshot_id = $lobbyId;
            $lobby->queue_id = 'communal';
            $lobby->password = 'test';
            $lobby->name = 'Hiya!';
            $lobby->save();

            (new GameService())->addPlayer(
                game: $lobby,
                player: Player::where('name', 'justa')->first(),
                team: 'home',
                isCaptain: true
            );

            $this->info("Lobby created, Slapshot ID: {$lobby->slapshot_id}");
        } catch (LobbyLimitException $e) {
            $this->error('Lobby limit reached.');
        }
    }
}
