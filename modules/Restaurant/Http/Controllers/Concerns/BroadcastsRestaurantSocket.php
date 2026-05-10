<?php

namespace Modules\Restaurant\Http\Controllers\Concerns;

use Modules\Restaurant\Services\RestaurantSocketBroadcaster;
use Modules\Restaurant\Services\RestaurantSocketEvents;

trait BroadcastsRestaurantSocket
{
    protected function restaurantSocketEmit(string $event, array $payload = []): void
    {
        try {
            app(RestaurantSocketBroadcaster::class)->emit($event, $payload);
        } catch (\Throwable $e) {
            \Log::warning('restaurantSocketEmit: ' . $e->getMessage());
        }
    }

    protected function restaurantSocketSync(string $scope, string $reason, array $extra = []): void
    {
        $this->restaurantSocketEmit(RestaurantSocketEvents::SYNC, array_merge([
            'scope' => $scope,
            'reason' => $reason,
        ], $extra));
    }
}
