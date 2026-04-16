<?php

namespace App\Services\System;

use Illuminate\Support\Facades\File;

/**
 * Alinea permisos de las carpetas de tenant (hyn/multi-tenant) para que el mismo usuario
 * que ejecuta PHP (p. ej. www-data / nginx) pueda leer y servir PDF/XML sin bloqueos
 * típicos de umask o de creación bajo otro usuario (CLI root vs FPM).
 */
class TenantStoragePermissionService
{
    /** Modo directorios: rwxr-xr-x */
    public const DIR_MODE = 0755;

    /** Modo archivos: rw-r--r-- */
    public const FILE_MODE = 0644;

    /**
     * Raíz donde hyn guarda cada website (ver FilesystemProvider del paquete).
     */
    public static function tenantStorageRoot(): string
    {
        $disk = config('tenancy.website.disk') ?: 'tenancy-default';
        $root = config("filesystems.disks.{$disk}.root");

        return $root ? rtrim($root, DIRECTORY_SEPARATOR) : storage_path('app' . DIRECTORY_SEPARATOR . 'tenancy' . DIRECTORY_SEPARATOR . 'tenants');
    }

    /**
     * Garantiza que exista la jerarquía app/tenancy/tenants con permisos de directorio correctos.
     */
    public static function ensureTenantsHierarchyExists(): void
    {
        $root = self::tenantStorageRoot();
        File::ensureDirectoryExists($root);
        if (PHP_OS_FAMILY !== 'Windows') {
            $cursor = $root;
            for ($i = 0; $i < 6 && is_dir($cursor); $i++) {
                @chmod($cursor, self::DIR_MODE);
                $parent = dirname($cursor);
                if ($parent === $cursor) {
                    break;
                }
                $cursor = $parent;
            }
        }
    }

    /**
     * Aplica permisos al directorio del tenant recién creado y, si aplica, a su contenido.
     */
    public static function applyToTenantUuid(string $uuid): void
    {
        if ($uuid === '' || $uuid === '.' || $uuid === '..') {
            return;
        }

        self::ensureTenantsHierarchyExists();

        $path = self::tenantStorageRoot() . DIRECTORY_SEPARATOR . $uuid;
        if (!is_dir($path)) {
            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        @chmod($path, self::DIR_MODE);
        self::chmodRecursiveUnix($path);
    }

    private static function chmodRecursiveUnix(string $baseDir): void
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
        } catch (\Throwable $e) {
            return;
        }

        foreach ($iterator as $item) {
            $real = $item->getRealPath();
            if ($real === false) {
                continue;
            }
            if ($item->isDir()) {
                @chmod($real, self::DIR_MODE);
            } else {
                @chmod($real, self::FILE_MODE);
            }
        }
    }
}
