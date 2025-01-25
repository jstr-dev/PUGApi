<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Services\Slapshot;
use App\Services\Steam;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

// TODO: nice views
class SteamController extends Controller
{
    public function authenticate(Request $request)
    {
        if (!$request->has('discord_id') || !$request->has('timestamp') || !$request->has('signature')) {
            return 'Invalid request.';
        }

        if ($request->timestamp < time() - config('services.pug.timestamp_tolerance') || $request->timestamp > time()) {
            return 'This auth URL has expired, please generate another one.';
        }

        $secret = config('services.pug.shared_secret');
        $trueSignature = hash_hmac('sha256', $request->discord_id . '-' . $request->timestamp, $secret);

        if ($request->signature !== $trueSignature) {
            return 'Invalid signature.';
        }

        session(['discord_id' => encrypt($request->discord_id)]);

        return Socialite::driver('steam')->redirect();
    }

    public function callback(Request $request, Steam $steam, Slapshot $slapshot)
    {
        if (!session()->has('discord_id')) {
            return 'Invalid request.';
        }

        $discordId = decrypt(session()->get('discord_id'));

        if (!config('app.debug')) {
            session()->forget('discord_id');
        }

        $matches = [];
        if (!preg_match('/^https:\/\/steamcommunity.com\/openid\/id\/(\d+)$/', $request->get('openid_claimed_id'), $matches)) {
            return 'Invalid request.';
        }

        $steamId = $matches[1];
        $slapshotId = $slapshot->getSlapshotID($steamId);

        if (
            Player::where('steam_id', $steamId)
                ->orWhere('discord_id', $discordId)
                ->orWhere('slapshot_id', $slapshotId)
                ->exists()
        ) {
            return 'You have already registered your account.';
        }

        $steamInfo = $steam->getPlayerInformation($steamId);

        $player = new Player();
        $player->steam_id = $steamId;
        $player->slapshot_id = $slapshotId;
        $player->discord_id = $discordId;
        $player->name = $steamInfo->personaname;
        $player->save();

        return 'ok';
    }
}
