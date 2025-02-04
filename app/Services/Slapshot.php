<?php

namespace App\Services;

use App\Exceptions\Slapshot\LobbyLimitException;
use App\Exceptions\Slapshot\UnknownAPIException;
use App\Exceptions\Slapshot\PlayerNotFound;
use App\Models\GameLobby;
use Exception;
use Illuminate\Support\Facades\Http;

class Slapshot
{
    private string $host;
    private string $key;

    function __construct()
    {
        $this->host = config('services.slapshot.host');
        $this->key = config('services.slapshot.key');
    }

    public function getSlapshotID(string $steamId): string
    {
        $res = $this->get("players/steam/$steamId");

        if ($res->notFound()) {
            throw new PlayerNotFound("No slapshot ID found.");
        }

        return $res->json()['id'];
    }

    public function getMatchDetails(string $matchId)
    {
        $res = $this->get("games/$matchId");
        return $res->json();
    }

    public function createLobby(string $name, string $password, bool $usePeriods, string $arena, int $mercyRule, int $teamSize): string
    {
        $res = $this->post('lobbies', [
            'region' => 'eu-west',
            'name' => $name,
            'password' => $password,
            'creator_name' => 'EUSL Pug Bot',
            'is_periods' => $usePeriods,
            'current_period' => 1,
            'initial_stats' => null,
            'arena' => $arena,
            'mercy_rule' => $mercyRule,
            'match_length' => 300,
            'team_size_limit' => $teamSize,
            'game_mode' => 'hockey',
            'initial_score' => [
                'home' => 0,
                'away' => 0,
            ],
            'webhook' => config('services.slapshot.webhook')
        ]);

        if (!$res->ok()) {
            throw new UnknownAPIException();
        }

        $res = (object) ($res->json() ?? []);

        if (!isset($res->success) || $res->success !== true) {
            if (str_contains($res->error, 'lobbies that have less than')) {
                throw new LobbyLimitException();
            }

            throw new UnknownAPIException();
        }

        return $res->lobby_id;
    }

    private function request(string $type, string $uri, array $payload = [], array $queryParameters = [], int $retries = 0)
    {
        $url = "{$this->host}/api/public/$uri";
        $request = Http::withToken($this->key);

        switch ($type) {
            case 'get':
                $response = $request->withUrlParameters($queryParameters)->get($url);
                break;
            case 'post':
                $response = $request->post($url, $payload);
                break;
            default:
                throw new Exception("Invalid Request Type");
        }

        if ($response->getStatusCode() === 429 && $retries < 3) {
            sleep(5);
            $retries++;

            return $this->request($type, $uri, $payload, $queryParameters, $retries);
        }

        return $response;
    }

    private function get(string $uri, array $query = [])
    {
        return $this->request(
            type: 'get',
            uri: $uri,
            queryParameters: $query
        );
    }

    private function post(string $uri, array $payload = [])
    {
        return $this->request(
            type: 'post',
            uri: $uri,
            payload: $payload
        );
    }
}
