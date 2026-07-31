<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\ChequeoDepositoRepository;
use App\Services\WhatsappApiService;
use App\Models\Sedes;
use Carbon\Carbon;
use Exception;

class VerificarChequeoHorario extends Command
{
    // Firma del comando para ejecutarlo manualmente o en el scheduler
    protected $signature = 'check:varillaje-horario';
    protected $description = 'Verifica si las sedes cargaron el varillaje a tiempo; si no, envía alerta.';
    protected $chequeoRepo;
    protected $whatsappService;

    public function __construct(ChequeoDepositoRepository $chequeoRepo, WhatsappApiService $whatsappService)
    {
        parent::__construct();
        $this->chequeoRepo = $chequeoRepo;
        $this->whatsappService = $whatsappService;
    }

    public function handle()
    {
        $ahora = Carbon::now('America/Caracas');
        $hoy = $ahora->toDateString();
        $horaActual = $ahora->format('H:i');

        // Determinamos qué turno deberíamos auditar según la hora de ejecución
        // Por ejemplo: Si corre a las 08:30, audita el Matutino. Si corre a las 16:30, el Vespertino.
        if ($horaActual >= '08:00' && $horaActual < '12:00') {
            $turnoAVerificar = 'Matutino';
        } elseif ($horaActual >= '16:00' && $horaActual < '20:00') {
            $turnoAVerificar = 'Nocturno';
        } else {
            return Command::SUCCESS;
        }

        // Traemos todas las sedes activas para verificar una por una
        $sedes = Sedes::all();
        $idDestino = config('services.whatsapp.dev_group_id', 'WHATSAPP_DEV_GROUP_ID');

        foreach ($sedes as $sede) {
            $existeChequeo = $this->chequeoRepo->existeChequeo($sede->id, $hoy, $turnoAVerificar);

            if (!$existeChequeo) {
                $mensaje = "🚨 *RECORDATORIO DE VARILLAJE* 🚨\n";
                $mensaje .= "Atención equipo de la sede *{$sede->nombre}*.\n\n";
                $mensaje .= "Aún no se ha realizado el Chequeo de Tanques de Combustible para el turno *{$turnoAVerificar}* de hoy ({$ahora->format('d/m/Y')}).\n\n";
                $mensaje .= "⚠️ Por favor, proceder con el varillaje y la carga de datos en el sistema a la brevedad.";

                try {
                    $this->whatsappService->enviarMensaje($idDestino, $mensaje);
                    $this->info("Alerta enviada para la sede: {$sede->nombre}");
                } catch (Exception $e) {
                    logger()->error("Fallo al enviar recordatorio de horario a WhatsApp: " . $e->getMessage());
                }
            }
        }

        return Command::SUCCESS;
    }
}