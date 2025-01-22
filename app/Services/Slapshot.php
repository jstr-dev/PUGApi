<?php

namespace App\Services;

use App\Exceptions\Slapshot\PlayerNotFound;
use Exception;
use Illuminate\Http\Client\HttpClientException;
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

    public function getSlapshotID(string $steamId)
    {
        $res = $this->get("players/steam/$steamId");

        if ($res->notFound()) {
            throw new PlayerNotFound("No slapshot ID found.");
        }

        return $res->json()['id'];
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
