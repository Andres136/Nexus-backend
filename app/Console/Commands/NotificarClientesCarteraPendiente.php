<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioCarteraClienteMail;
use App\Models\Crm\Cliente;
use App\Services\Crm\GestionCarteraService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotificarClientesCarteraPendiente extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notificar-clientes-cartera-pendiente';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un recordatorio semanal por correo a los clientes con cartera vencida o próxima a vencer';

    public function handle(GestionCarteraService $service)
    {
        $clienteIds = $service->clientesConCarteraPendiente();
        $enviados = 0;

        foreach ($clienteIds as $clienteId) {
            $resumen = $service->resumenCarteraCliente($clienteId);
            if (!$resumen) {
                continue;
            }

            $cliente = Cliente::find($clienteId);
            if (!$cliente || !$cliente->email) {
                continue;
            }

            Mail::to($cliente->email)->send(new RecordatorioCarteraClienteMail($cliente, $resumen));
            $enviados++;
        }

        $this->info("Recordatorios de cartera enviados a {$enviados} cliente(s).");

        return 0;
    }
}
