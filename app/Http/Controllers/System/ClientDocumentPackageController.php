<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\System\Client;
use App\Models\System\ClientDocumentPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Document\Helpers\DocumentHelper;
use Carbon\Carbon;

class ClientDocumentPackageController extends Controller
{
    public function index()
    {
        return view('system.document_packages.index');
    }

    public function summary($clientId)
    {
        $client = Client::findOrFail($clientId);
        $cycle = $this->getCurrentCycle($client);

        $packages = ClientDocumentPackage::query()
            ->activeForCycle($client->id, $cycle['cycle_start_at'], $cycle['cycle_end_at'])
            ->orderBy('created_at')
            ->get()
            ->map(function (ClientDocumentPackage $package) {
                return [
                    'id' => $package->id,
                    'units_total' => $package->units_total,
                    'price' => $package->price !== null ? (float) $package->price : null,
                    'units_consumed' => $package->units_consumed,
                    'units_remaining' => $package->remaining_units,
                    'include_sale_notes' => $package->include_sale_notes,
                    'status' => $package->status,
                    'cycle_start_at' => $package->cycle_start_at->format('Y-m-d'),
                    'cycle_end_at' => $package->cycle_end_at->format('Y-m-d'),
                    'created_at' => $package->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $remaining = $packages->sum('units_remaining');

        return [
            'success' => true,
            'data' => [
                'client_id' => $client->id,
                'cycle_start_at' => $cycle['cycle_start_at'],
                'cycle_end_at' => $cycle['cycle_end_at'],
                'packages' => $packages,
                'remaining_units' => (int) $remaining,
            ],
        ];
    }

    public function store(Request $request)
    {
        $priceMap = $this->getUnitsPriceMap();

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'units_total' => ['required', 'integer', Rule::in(array_keys($priceMap))],
            'include_sale_notes' => ['nullable', 'boolean'],
        ]);

        $client = Client::findOrFail($data['client_id']);
        $cycle = $this->getCurrentCycle($client);

        $includeSaleNotes = array_key_exists('include_sale_notes', $data)
            ? (bool) $data['include_sale_notes']
            : (bool) optional($client->plan)->include_sale_notes_limit_documents;

        $package = new ClientDocumentPackage();
        $package->fill([
            'client_id' => $client->id,
            'units_total' => (int) $data['units_total'],
            'price' => (float) $priceMap[(int) $data['units_total']],
            'units_consumed' => 0,
            'include_sale_notes' => $includeSaleNotes,
            'cycle_start_at' => $cycle['cycle_start_at'],
            'cycle_end_at' => $cycle['cycle_end_at'],
            'status' => 'active',
        ]);
        $package->save();

        return [
            'success' => true,
            'message' => 'Paquete registrado con éxito',
            'data' => [
                'id' => $package->id,
            ],
        ];
    }

    public function cancel($packageId)
    {
        $package = ClientDocumentPackage::findOrFail($packageId);

        if ($package->status !== 'active') {
            return [
                'success' => false,
                'message' => 'El paquete no se puede cancelar.',
            ];
        }

        $package->status = 'canceled';
        $package->save();

        return [
            'success' => true,
            'message' => 'Paquete cancelado.',
        ];
    }

    private function getCurrentCycle(Client $client): array
    {
        $now = Carbon::now();

        if ($client->start_billing_cycle) {
            $startEnd = DocumentHelper::getStartEndDateForFilterDocument($client->start_billing_cycle);
            $cycleStart = $startEnd['start_date']->copy()->startOfDay();
            $cycleEnd = $cycleStart->copy()->addMonthNoOverflow()->subDay()->endOfDay();

            return [
                'cycle_start_at' => $cycleStart->format('Y-m-d'),
                'cycle_end_at' => $cycleEnd->format('Y-m-d'),
                'count_end_at' => $now->format('Y-m-d'),
            ];
        }

        return [
            'cycle_start_at' => $now->copy()->startOfMonth()->format('Y-m-d'),
            'cycle_end_at' => $now->copy()->endOfMonth()->format('Y-m-d'),
            'count_end_at' => $now->format('Y-m-d'),
        ];
    }

    private function getUnitsPriceMap(): array
    {
        return [
            50 => 10,
            100 => 15,
            200 => 30,
        ];
    }
}
