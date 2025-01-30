<?php

namespace App\Console\Commands;

use App\Models\Queue;
use Illuminate\Console\Command;

class CreateQueue extends Command
{
    protected $signature = 'queue:new';
    protected $description = 'Create a new pug queue.';

    public function handle()
    {
        $id = $this->ask('Please enter a queue id');

        if (Queue::where('id', '=', $id)->exists()) {
            $this->error('Queue already exists.');
            return;
        }

        $name = $this->ask('Please enter a queue name');
        $description = $this->ask('Please enter a queue description');
        $discordChannelId = $this->ask('Please enter a discord channel id');

        $queue = new Queue();
        $queue->id = $id;
        $queue->name = $name;
        $queue->discord_channel_id = $discordChannelId;
        $queue->description = $description;
        $queue->save();

        $this->info('Queue saved.');
    }
}
