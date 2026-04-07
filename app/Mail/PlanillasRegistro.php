<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlanillasRegistro extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        
    }

    public function build()
    {
        return $this->subject('Planillas de Registro - TuCombustible')
                    ->attachFromStorage('public/planillas/declaracion_bajo_fe_de_juramento_2025-1.doc', 'declaracion_bajo_fe_de_juramento_2025-1.doc', [
                        'mime' => 'application/msword',
                    ])
                    ->attachFromStorage('public/planillas/guia_tramitacion_de_cupos.pdf', 'guia_tramitacion_de_cupos.pdf', [
                        'mime' => 'application/pdf',
                    ])
                    ->attachFromStorage('public/planillas/modelo_carta_solicitud_cupo_gasoil.docx', 'modelo_carta_solicitud_cupo_gasoil.docx', [
                        'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->attachFromStorage('public/planillas/planilla_solicitud_cupo_gasoil.pdf', 'planilla_solicitud_cupo_gasoil.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}