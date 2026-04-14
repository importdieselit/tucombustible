<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetMensualCupos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle()
    {
        $mes = now()->month;
        $anio = now()->year;

        $clientes = \App\Models\Cliente::where('status', 2)->get(); // Solo clientes aprobados

        foreach ($clientes as $cliente) {
            // Buscamos el último cupo GASCO registrado para este cliente
            $ultimoCupo = \App\Models\GascoCupoMensual::where('cliente_id', $cliente->id)
                ->orderBy('anio', 'desc')
                ->orderBy('mes', 'desc')
                ->first();

            // Si no existe un registro previo, usamos el 'cupo' (Aprobado) de la tabla clientes
            $litrosParaElMes = $ultimoCupo ? $ultimoCupo->litros_autorizados : $cliente->cupo;

            // Creamos el registro para el nuevo mes (Persistencia automática)
            \App\Models\GascoCupoMensual::updateOrCreate(
                ['cliente_id' => $cliente->id, 'mes' => $mes, 'anio' => $anio],
                [
                    'litros_autorizados' => $litrosParaElMes,
                    'litros_consumidos' => 0
                ]
            );

            // Sincronizamos el disponible en la tabla clientes
            $cliente->update(['disponible' => $litrosParaElMes]);
        }

        $this->info("Cupos reiniciados para el periodo $mes-$anio");
    }
}
