<?php

namespace App\Console\Commands;

use App\Exceptions\Slapshot\PlayerNotFound;
use App\Services\Slapshot;
use Illuminate\Console\Command;

class GetSlapshotId extends Command
{
    protected $signature = 'slapshot:get-id {steamid}';

    protected $description = 'Get slapshot ID by steamid.';

    public function handle()
    {
        $steamId = $this->argument('steamid');
        $slapshot = new Slapshot();

        try {
            $id = $slapshot->getSlapshotID($steamId);
            $this->info("Slapshot ID: $id");
        } catch (PlayerNotFound $e) {
            $this->error("No slapshot ID found.");
        }
    }
}
