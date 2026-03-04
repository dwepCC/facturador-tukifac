<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;

class AppVersionController extends Controller
{
    public function tukifac()
    {
        return response()->json([
            "android" => [
                "min_version" => "1.1.0",
                "latest_version" => "1.1.1",
                "store_url" => "https://play.google.com/store/apps/details?id=com.tukifacapp",
                "release_notes" => "Mejoras de seguridad y nuevas funciones. Es necesario actualizar."
            ],
            "windows" => [
                "min_version" => "1.1.0",
                "latest_version" => "1.1.1",
                "download_url" => "https://drive.google.com/file/d/15w1551co01_QxMXa8bQ690irux7iR-KJ/view?usp=drive_link",
                "release_notes" => "Correcciones críticas para Windows y nuevas funciones de impresión."
            ]
        ]);
    }

    public function tukichef()
    {
        return response()->json([
            "android" => [
                "min_version" => "1.4.6",
                "latest_version" => "1.4.7",
                "store_url" => "https://play.google.com/store/apps/details?id=com.tukichef",
                "release_notes" => "Mejoras de seguridad y nuevas funciones. Es necesario actualizar."
            ],
            "windows" => [
                "min_version" => "1.4.6",
                "latest_version" => "1.4.7",
                "download_url" => "https://tukichef.com/descargas/windows/latest.exe",
                "release_notes" => "Correcciones críticas para Windows y nuevas funciones de impresión."
            ]
        ]);
    }
}
