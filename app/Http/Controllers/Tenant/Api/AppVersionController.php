<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;

class AppVersionController extends Controller
{
    public function tukifac()
    {
        return response()->json([
            "android" => [
                "min_version" => "1.1.9",
                "latest_version" => "1.2.0",
                "store_url" => "https://play.google.com/store/apps/details?id=com.tukifacapp",
                "release_notes" => "Mejoras de seguridad y nuevas funciones. Es necesario actualizar."
            ],
            "windows" => [
                "min_version" => "1.1.9",
                "latest_version" => "1.2.0",
                "download_url" => "https://drive.google.com/file/d/1he2xl3hlYE71FNGyVl99SmPlw_v1Z9Di/view?usp=drive_link",
                "release_notes" => "Correcciones críticas para Windows y nuevas funciones de impresión."
            ]
        ]);
    }

    public function tukichef()
    {
        return response()->json([
            "android" => [
                "min_version" => "1.5.1",
                "latest_version" => "1.5.1",
                "store_url" => "https://play.google.com/store/apps/details?id=com.tukichef",
                "release_notes" => "Mejoras de seguridad y nuevas funciones. Es necesario actualizar."
            ],
            "windows" => [
                "min_version" => "1.5.1",
                "latest_version" => "1.5.1",
                "download_url" => "https://tukichef.com/descargas/windows/latest.exe",
                "release_notes" => "Correcciones críticas para Windows y nuevas funciones de impresión."
            ]
        ]);
    }
}
