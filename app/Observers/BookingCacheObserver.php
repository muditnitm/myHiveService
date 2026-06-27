<?php

namespace App\Observers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingCacheObserver
{
    public function saved($model): void
    {
        $this->bustCache($model->business_id ?? null);
    }

    public function deleted($model): void
    {
        $this->bustCache($model->business_id ?? null);
    }

    private function bustCache(?int $businessId): void
    {
        if (! $businessId) {
            return;
        }

        try {
            Http::timeout(3)
                ->withHeaders(['Host' => 'myhivee.com'])
                ->post('http://127.0.0.1:8081/internal/cache-bust', [
                    'business_id' => $businessId,
                ]);
        } catch (\Exception $e) {
            Log::warning('booking cache bust failed: ' . $e->getMessage());
        }
    }
}
