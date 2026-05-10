<?php

namespace Modules\Restaurant\Services;

use Hyn\Tenancy\Contracts\CurrentHostname;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RestaurantSocketBroadcaster
{
    /**
     * Identificador de sala Socket.IO para el tenant actual (FQDN del hostname).
     */
    public function tenantRoom(): string
    {
        $hostname = app(CurrentHostname::class);

        if ($hostname && ! empty($hostname->fqdn)) {
            return 'tenant:' . sha1(strtolower($hostname->fqdn));
        }

        return 'tenant:' . sha1((string) config('app.url'));
    }

    /**
     * Misma clave expuesta al cliente (GET configuración / login) para unirse a la sala.
     */
    public function tenantRoomPublicKey(): string
    {
        return $this->tenantRoom();
    }

    /**
     * Emite un evento a todos los clientes conectados al room del tenant actual.
     */
    public function emit(string $event, array $payload = []): void
    {
        if (! config('restaurant_socket.enabled')) {
            return;
        }

        $secret = (string) config('restaurant_socket.bridge_secret');
        if ($secret === '') {
            Log::warning('Restaurant socket: SOCKET_BRIDGE_SECRET vacío; emisión omitida.');

            return;
        }

        $bridge = rtrim((string) config('restaurant_socket.bridge_url'), '/');
        $timeout = (float) config('restaurant_socket.bridge_timeout', 2);

        $body = [
            'room' => $this->tenantRoom(),
            'event' => $event,
            'payload' => array_merge($payload, [
                'schema_version' => RestaurantSocketEvents::SCHEMA_VERSION,
                'emitted_at' => now()->toIso8601String(),
                'tenant_room' => $this->tenantRoom(),
            ]),
        ];

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'X-Restaurant-Socket-Secret' => $secret,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($bridge . '/internal/broadcast', $body);

            if (! $response->successful()) {
                Log::warning('Restaurant socket bridge HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Restaurant socket bridge failed: ' . $e->getMessage());
        }
    }
}
