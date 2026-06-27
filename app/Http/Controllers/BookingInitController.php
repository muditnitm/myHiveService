<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Models\Setting;
use App\Models\userActiveModule;
use Illuminate\Http\JsonResponse;

class BookingInitController extends Controller
{
    public function init(int $businessId): JsonResponse
    {
        $business = Business::where('id', $businessId)->where('status', 'active')->first();

        if (! $business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $services = Service::where('business_id', $businessId)
            ->get(['id', 'name', 'price', 'is_free', 'duration', 'description', 'category_id']);

        $locations = Location::where('business_id', $businessId)
            ->get(['id', 'name', 'address']);

        $settingKeys = [
            'booking_mode', 'maximum_slot', 'defult_timezone',
            'custom_field_enable', 'default_status', 'stripe_is_on',
            'paypal_is_on', 'stripe_key',
        ];

        $rawSettings = Setting::where('business', $businessId)
            ->whereIn('key', $settingKeys)
            ->pluck('value', 'key');

        $settings = [
            'booking_mode'        => $rawSettings['booking_mode'] ?? 'manual',
            'maximum_slot'        => (int) ($rawSettings['maximum_slot'] ?? 1),
            'defult_timezone'     => $rawSettings['defult_timezone'] ?? 'Asia/Kolkata',
            'custom_field_enable' => $rawSettings['custom_field_enable'] ?? 'off',
            'default_status'      => $rawSettings['default_status'] ?? 'Pending',
        ];

        $paymentMethods = ['Manually'];

        $activeModules = userActiveModule::where('user_id', $business->created_by)
            ->pluck('module')
            ->toArray();

        if (
            in_array('Stripe', $activeModules) &&
            ($rawSettings['stripe_is_on'] ?? 'off') === 'on' &&
            ! empty($rawSettings['stripe_key'])
        ) {
            $paymentMethods[] = 'Stripe';
        }

        if (
            in_array('Paypal', $activeModules) &&
            ($rawSettings['paypal_is_on'] ?? 'off') === 'on'
        ) {
            $paymentMethods[] = 'Paypal';
        }

        return response()->json([
            'business'        => ['id' => $business->id, 'name' => $business->name, 'slug' => $business->slug],
            'services'        => $services,
            'locations'       => $locations,
            'settings'        => $settings,
            'payment_methods' => $paymentMethods,
        ]);
    }
}
