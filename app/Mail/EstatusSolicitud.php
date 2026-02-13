<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\CaptacionCliente;
use Illuminate\Contracts\Queue\ShouldQueue;

class EstatusSolicitud extends Mailable
{
    use Queueable, SerializesModels;

    public $prospecto;
    public $tipo;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->prospecto = $prospecto;
        $this->tipo = $tipo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.estatus_solicitud')
                    ->subject($this->tipo == 'aprobado' ? 'Solicitud Aprobada' : 'Información sobre su Solicitud');
    }
}
