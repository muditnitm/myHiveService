<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SsoController extends Controller
{
    public function login(Request $request, string $token)
    {
        $userId = Cache::pull('sso_service_' . $token);

        if (! $userId) {
            abort(401, 'SSO token expired or invalid.');
        }

        $user = User::find($userId);

        if (! $user) {
            abort(404, 'User not found.');
        }

        Auth::login($user, false);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
