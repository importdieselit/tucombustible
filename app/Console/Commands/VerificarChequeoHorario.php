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

        // Determinamos el turno
        if ($horaActual >= '08:00' && $horaActual < '12:00') {
            $turnoAVerificar = 'Matutino';
        } elseif ($horaActual >= '16:00' && $horaActual < '20:00') {
            $turnoAVerificar = 'Nocturno';
        } else {
            $this->warn(" Fuera de horario de auditoría de varillaje ({$horaActual}). Proceso finalizado.");
            return Command::SUCCESS;
        }

        $sedes = Sedes::all();

        if ($sedes->isEmpty()) {
            $this->error("❌ No se encontraron sedes para auditar.");
            return Command::FAILURE;
        }

        $idDestino = config('services.whatsapp.dev_group_id', 'WHATSAPP_DEV_GROUP_ID');
        $resumenAuditoria = [];

        // Encabezado visual
        $this->info("==================================================");
        $this->info(" 🔍 AUDITORÍA DE VARILLAJE - TURNO " . strtoupper($turnoAVerificar));
        $this->info(" Fecha: " . $ahora->format('d/m/Y H:i:s'));
        $this->info("==================================================\n");

        // Inicio de barra de progreso en consola
        $this->output->progressStart($sedes->count());

        foreach ($sedes as $sede) {
            $existeChequeo = $this->chequeoRepo->existeChequeo($sede->id, $hoy, $turnoAVerificar);

            if ($existeChequeo) {
                $status = '<fg=green>COMPLETADO</>';
                $alerta = 'No requerida';
            } else {
                $mensaje = "🚨 *RECORDATORIO DE VARILLAJE* 🚨\n";
                $mensaje .= "Atención equipo de la sede *{$sede->nombre}*.\n\n";
                $mensaje .= "Aún no se ha realizado el Chequeo de Tanques de Combustible para el turno *{$turnoAVerificar}* de hoy ({$ahora->format('d/m/Y')}).\n\n";
                $mensaje .= "⚠️ Por favor, proceder con el varillaje y la carga de datos en el sistema a la brevedad.";

                try {
                    $this->whatsappService->enviarMensaje($mensaje, $idDestino);
                    $status = '<fg=red>PENDIENTE</>';
                    $alerta = '<fg=yellow>Enviada</>';
                } catch (Exception $e) {
                    $status = '<fg=red>PENDIENTE</>';
                    $alerta = '<fg=red>Error al enviar</>';
                    logger()->error("Fallo al enviar recordatorio de horario a WhatsApp: " . $e->getMessage());
                }
            }

            // Datos para la tabla final
            $resumenAuditoria[] = [
                'ID' => $sede->id,
                'Sede' => $sede->nombre,
                'Estado' => $status,
                'Alerta WA' => $alerta
            ];

            // Avanzar barra de progreso
            $this->output->progressAdvance();
            
            // Simulación ligera para fluidez visual si son pocas sedes
            usleep(100000); 
        }

        $this->output->progressFinish();
        $this->newLine();

        // Renderizado de tabla estructurada con resultados
        $this->table(
            ['ID', 'Sede', 'Estado Varillaje', 'Alerta WhatsApp'],
            $resumenAuditoria
        );

        $this->info(" Auditoría de varillaje completada exitosamente.");

        return Command::SUCCESS;
    }
}