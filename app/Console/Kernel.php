<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        // OPTIMIZACIÓN: Agregar withoutOverlapping() para evitar ejecuciones simultáneas
        // y timeouts para evitar procesos colgados
        
        // Ejecutar tareas de tenants cada minuto (con protección contra solapamiento)
        $schedule->command('tenancy:run tenant:run')
            ->everyMinute()
            ->withoutOverlapping(5) // Máximo 5 minutos de ejecución
            ->runInBackground(); // Ejecutar en background para no bloquear
        
        // Se ejecutara por hora guardando estado de cpu y memoria (windows/linux)
        // OPTIMIZACIÓN: Cambiar a cada hora como indica el comentario
        $schedule->command('status:server')
            ->hourly()
            ->withoutOverlapping(2) // Máximo 2 minutos de ejecución
            ->runInBackground();
        
        // Comando de ordenes de pago cada minuto (con protección)
        $schedule->command('order:payments')
            ->everyMinute()
            ->withoutOverlapping(10) // Máximo 10 minutos de ejecución
            ->appendOutputTo(storage_path('logs/order_create.log'))
            ->runInBackground();
        
        // Llena las tablas para libro mayor - Se desactiva CMAR - buscar opcion de url
        // $schedule->command('account_ledger:fill')->hourly();
        
        //restaurar base de datos demo para restaurant
        // $schedule->command('database:restoredemo')->dailyAt('23:50');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
