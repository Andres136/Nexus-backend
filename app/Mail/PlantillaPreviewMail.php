<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlantillaPreviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public $plantilla;

    public function __construct($plantilla)
    {
        $this->plantilla = $plantilla;
    }

    public function build()
    {
        return $this->subject($this->plantilla->nombre ?? 'Vista previa de plantilla')
                    ->view('emails.marketing')
                    ->with([
                        'titulo'           => $this->plantilla->nombre,
                        'contenido_html'   => $this->plantilla->contenido_html,
                        'logos_empresas'   => $this->plantilla->logos_empresas,
                        'imagenes'         => $this->plantilla->imagenes,
                        'certificaciones'  => $this->plantilla->certificaciones,
                        'video_url'        => $this->plantilla->video_url,
                        'redes_sociales'   => $this->plantilla->redes_sociales,
                        'descargas'        => $this->plantilla->descargas,
                    ]);
    }
}
