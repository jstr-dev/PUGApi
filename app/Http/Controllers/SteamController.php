<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return 'yes!';
    }
}
