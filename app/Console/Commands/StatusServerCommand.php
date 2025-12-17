<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\System\HistoryResource;
use App\Http\Controllers\System\StatusController;
use Carbon\Carbon;

class StatusServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'status:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Se ejecutara por hora guardando estado de cpu y memoria (windows/linux)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('The command was started');

        // Validar si ya hay un registro en este minuto para evitar duplicados
        $last = HistoryResource::orderBy('created_at', 'desc')->first();
        $now = Carbon::now();
        
        // Solo guardar si no hay registro o si el último registro es de hace más de 1 minuto
        if ($last == null || $now->diffInMinutes($last->created_at) >= 1) {
            $this->saveRecord();
        } else {
            $this->info('Ya existe un registro en este minuto, omitiendo guardado');
        }

        $this->info("The command is finished");
    }

    public function saveRecord()
    {
        $statusController = new StatusController();
        $memory = $statusController->memory(false);
        $cpu = $statusController->cpu();

        $history = new HistoryResource();
        $history->cpu_percent = $cpu['cpu'];
        $history->memory_total = $memory['total'];
        $history->memory_free = $memory['free'];
        $history->memory_used = $memory['used'];
        $history->save();
        
        // ELIMINADO: sleep(15) - Esto estaba bloqueando procesos innecesariamente
        // Si necesitas múltiples muestras, ejecuta el comando varias veces con delay
    }
}
