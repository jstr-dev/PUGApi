<?php

namespace App\Console\Commands;

use App\Services\GameService;
use Illuminate\Console\Command;

class DebugEloDifference extends Command
{
    protected $signature = 'debug:elo-difference {elo1} {elo2} {--lost} {--k=}';
    protected $description = 'Debug elo diff';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $elo1 = $this->argument('elo1');
        $elo2 = $this->argument('elo2');
        $won = empty($this->option('lost'));
        $k = $this->option('k') ?? 32;

        $gameService = new GameService();
        $this->info('Elo diff: ' . $gameService->calculateEloDifference($elo1, $elo2, $won, $k));
    }
}
