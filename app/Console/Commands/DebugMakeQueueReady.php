<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\Queue;
use App\Models\QueuePlayers;
use Illuminate\Console\Command;

class DebugMakeQueueReady extends Command
{
    protected $signature = 'debug:make-queue-ready {queueId} {--dummyCount=}';
    protected $description = 'Add dummy players to a queue ready for testing/';


    public function handle()
    {
        $dummyCount = $this->option('dummyCount') ?? $this->getDummyCount();

        for ($i = 0; $i < $dummyCount; $i++) {
            $player = Player::firstOrNew(['discord_id' => 'dummy' . $i]);
            $player->name = 'Dummy Player ' . $i;
            $player->slapshot_id = $i;
            $player->steam_id = $i;
            $player->save();

            QueuePlayers::firstOrNew(['player_id' => $player->getKey(), 'queue_id' => $this->argument('queueId')])->save();
        }

        $this->info('Added ' . $dummyCount . ' dummy players to queue.');
    }

    private function getDummyCount(): int
    {
        $queue = Queue::where('id', '=', $this->argument('queueId'))->first();
        return $queue->getMaxPlayerCount() - 1;
    }
}
