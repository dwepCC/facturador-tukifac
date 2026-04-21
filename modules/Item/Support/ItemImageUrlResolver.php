<?php

namespace Modules\Item\Support;

/**
 * Resuelve URLs públicas de imágenes de ítems de forma única para APIs y listados.
 */
final class ItemImageUrlResolver
{
    private const PLACEHOLDER = 'imagen-no-disponible.jpg';

    public static function itemImage(?string $filename): string
    {
        if ($filename === null || $filename === '' || $filename === self::PLACEHOLDER) {
            return asset('logo/' . self::PLACEHOLDER);
        }

        return asset('storage/uploads/items/' . $filename);
    }
}
