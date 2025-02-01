<?php

namespace App\Console\Commands;

use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Console\Command;

class ResetQueue extends Command
{
    protected $signature = 'queue:reset {id} {--kick}';
    protected $description = 'Command description';

    public function handle()
    {
        $queueService = new QueueService();
        $id = $this->argument('id');
        $dontKick = empty($this->option('kick'));
        $queue = Queue::find($id)->first();
        $queueService->reset($queue, $dontKick);
        $this->info('Done.');
    }
}
