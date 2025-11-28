<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlanillasGeneradas extends Mailable
{
    use Queueable, SerializesModels;

    public $captacion;
    public $paths;

    public function __construct($captacion, $paths = [])
    {
        $this->captacion = $captacion;
        $this->paths = $paths;
    }

    public function build()
    {
        $m = $this->subject('Planillas y Documentos - Solicitud de Cupo')
            ->view('emails.planillas_generadas')
            ->with(['captacion' => $this->captacion]);

        foreach ($this->paths as $p) {
            $m->attach(storage_path('app/public/'.$p));
        }

        return $m;
    }
}
