<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessHours;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InternalController extends Controller
{
    private function requireLocalhost(Request $request): bool
    {
        return in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1']);
    }

    public function provision(Request $request): JsonResponse
    {
        if (! $this->requireLocalhost($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'username' => 'required|string|max:255',
        ]);

        $existing = User::where('email', $request->email)->where('type', 'company')->first();

        if ($existing) {
            $business = Business::where('created_by', $existing->id)->first();
            return response()->json([
                'already_exists'      => true,
                'service_user_id'     => $existing->id,
                'service_business_id' => $business?->id,
            ]);
        }

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make(Str::random(32)),
            'type'              => 'company',
            'email_verified_at' => now(),
            'is_enable_login'   => 1,
            'is_disable'        => 1,
            'total_user'        => -1,
            'total_business'    => -1,
            'created_by'        => 0,
        ]);

        $companyRole = Role::where('name', 'company')->first();
        if ($companyRole) {
            $user->addRole($companyRole);
        }

        $business = new Business();
        $business->name        = $request->name;
        $business->form_type   = 'form-layout';
        $business->layouts     = 'Formlayout1';
        $business->theme_color = 'color1-Formlayout1';
        $business->created_by  = $user->id;
        $business->save();

        $user->active_business = $business->id;
        $user->business_id     = $business->id;
        $user->created_by      = $user->id;
        $user->save();

        User::CompanySetting($user->id, $business->id);
        $user->MakeRole();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $day) {
            BusinessHours::create([
                'day_name'    => $day,
                'start_time'  => '09:00:00',
                'end_time'    => '18:00:00',
                'day_off'     => $day === 'Sunday' ? 'on' : 'off',
                'business_id' => $business->id,
                'created_by'  => $user->id,
            ]);
        }

        $plan = Plan::where('is_free_plan', 1)->first();
        if ($plan) {
            $user->assignPlan($plan->id, 'Month', $plan->modules, 0, $user->id);
        }

        return response()->json([
            'already_exists'      => false,
            'service_user_id'     => $user->id,
            'service_business_id' => $business->id,
        ]);
    }

    public function generateSso(Request $request): JsonResponse
    {
        if (! $this->requireLocalhost($request)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->where('type', 'company')->first();

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $token = Str::random(64);
        Cache::put('sso_service_' . $token, $user->id, now()->addMinutes(5));

        return response()->json([
            'login_url' => url('/sso/' . $token),
        ]);
    }
}
