<?php

namespace App\Http\Controllers\System\Api;

use App\Http\Controllers\Controller;
use App\Models\System\Client;
use App\Models\System\Configuration;
use Illuminate\Http\Request;

class CompanySearchController extends Controller
{
    public function search($number)
    {
        $client = Client::where('number', $number)
            ->select('id', 'name', 'hostname_id')
            ->with(['hostname' => function ($query) {
                $query->select('id', 'fqdn');
            }])
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        $configuration = Configuration::first();
        $token = $configuration ? $configuration->token_apiruc : null;

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $client->name,
                'subdomain' => optional($client->hostname)->fqdn,
                'token_apiruc' => $token
            ]
        ]);
    }
}
