<?php

namespace App\Services\Tenant;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Read-through cache para datos “calientes” del tenant.
 *
 * Aislamiento entre tenants: el driver {@see \App\Providers\CacheServiceProvider} `redis_tenancy`
 * antepone el UUID del website (FQDN → hostname) a todas las claves Redis. Las constantes aquí
 * son solo el sufijo lógico (v1:...); no sustituyen ese prefijo.
 *
 * Aislamiento entre usuarios del mismo tenant: usar sufijos `:user:{id}` o `:client:{id}` cuando
 * la respuesta depende del usuario autenticado o del cliente multi-empresa.
 */
final class TenantReadThroughCache
{
    /** Configuración global del tenant (web); invalidar al guardar Configuration. */
    public const KEY_CONFIGURATION_RECORD = 'v1:configuration:record';

    /** Plan / facturación SaaS; mismo payload para todo el tenant. */
    public const KEY_INFO_PLAN = 'v1:cuenta:info_plan';

    /** Tipo de cambio por fecha (Y-m-d). */
    public const KEY_EXCHANGE_PREFIX = 'v1:services:exchange:';

    /** Establecimiento activo del usuario (auth). */
    public const KEY_ESTABLISHMENT_ACTIVE_USER_PREFIX = 'v1:establishment:active:user:';

    /** Payload pesado API móvil/POS (por usuario API). */
    public const KEY_API_COMPANY_USER_PREFIX = 'v1:api:company:user:';

    /** Lista multi-usuario por cliente origen. */
    public const KEY_MULTI_USERS_RECORDS_CLIENT_PREFIX = 'v1:multi-users:records:client:';

    public const TTL_CONFIGURATION_RECORD_SECONDS = 300;

    public const TTL_INFO_PLAN_SECONDS = 300;

    public const TTL_ESTABLISHMENT_ACTIVE_SECONDS = 180;

    public const TTL_API_COMPANY_SECONDS = 120;

    public const TTL_MULTI_USERS_RECORDS_SECONDS = 300;

    public static function repository(): Repository
    {
        return Cache::store(config('cache.default'));
    }

    public static function exchangeKey(string $dateYmd): string
    {
        return self::KEY_EXCHANGE_PREFIX . $dateYmd;
    }

    /** TTL hasta fin de día (America/Lima) para tipos de cambio diarios. */
    public static function exchangeTtlSeconds(string $dateYmd): int
    {
        try {
            $end = Carbon::parse($dateYmd, 'America/Lima')->endOfDay();
        } catch (\Throwable $e) {
            return 3600;
        }
        $seconds = (int) now('America/Lima')->diffInSeconds($end, false);

        return max(60, min(abs($seconds), 86400));
    }

    public static function establishmentActiveUserKey(int $userId): string
    {
        return self::KEY_ESTABLISHMENT_ACTIVE_USER_PREFIX . $userId;
    }

    public static function apiCompanyUserKey(int $userId): string
    {
        return self::KEY_API_COMPANY_USER_PREFIX . $userId;
    }

    public static function multiUsersRecordsClientKey(int $clientId): string
    {
        return self::KEY_MULTI_USERS_RECORDS_CLIENT_PREFIX . $clientId;
    }

    public static function forgetConfigurationRecord(): void
    {
        self::repository()->forget(self::KEY_CONFIGURATION_RECORD);
    }

    public static function forgetInfoPlan(): void
    {
        self::repository()->forget(self::KEY_INFO_PLAN);
    }

    public static function forgetExchangeForDate(string $dateYmd): void
    {
        self::repository()->forget(self::exchangeKey($dateYmd));
    }

    public static function forgetEstablishmentActiveForUser(int $userId): void
    {
        self::repository()->forget(self::establishmentActiveUserKey($userId));
    }

    public static function forgetApiCompanyForUser(int $userId): void
    {
        self::repository()->forget(self::apiCompanyUserKey($userId));
    }

    public static function forgetMultiUsersRecordsForClient(int $clientId): void
    {
        self::repository()->forget(self::multiUsersRecordsClientKey($clientId));
    }

    /**
     * @template T
     * @param  Closure():T  $callback
     * @return T
     */
    public static function remember(string $key, int $ttlSeconds, Closure $callback)
    {
        return self::repository()->remember($key, $ttlSeconds, $callback);
    }
}
