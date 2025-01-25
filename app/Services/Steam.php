<?php

namespace App\Services;

use Http;

class Steam
{
    public function getPlayerInformation(string $steamId): object
    {
        $req = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=' . config('services.steam.api_key') . '&steamids=' . $steamId;
        $res = Http::get($req);

        if (!$res->ok()) {
            throw new \Exception('Steam API Issue.');
        }

        return (object) $res->json()['response']['players'][0];
    }
}
