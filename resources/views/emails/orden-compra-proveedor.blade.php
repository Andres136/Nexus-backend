@extends('layouts.email-modern')

@section('content')
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px;">
    <tr>
        <td>
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background: #ffffff; border-radius: 8px; padding: 20px; border: 1px solid #e5e7eb;">
                <tr>
                    <td style="text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px;">
                        <h1 style="color: #2563eb; font-size: 20px; margin: 0;">
                            📄 Orden de Compra #{{ $orden->numero_orden }}
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="font-size: 14px; color: #374151; line-height: 1.6; padding-top: 10px;">
                        <p>Estimado proveedor <strong style="color: #111827;">{{ $orden->proveedor->nombre ?? 'Desconocido' }}</strong>,</p>

                        <p>
                            Le informamos que se ha generado una nueva orden de compra el 
                            <strong>{{ $orden->fecha->format('d/m/Y') }}</strong>.
                        </p>

                        <p>
                            Por favor revise el documento adjunto y confirme la recepción a la mayor brevedad posible.
                        </p>
                    </td>
                </tr>

              

                <tr>
                    <td style="padding-top: 30px; font-size: 13px; color: #6b7280; border-top: 1px solid #e5e7eb;">
                        <p style="margin: 0;">Saludos cordiales,</p>
                        <p style="margin: 0; font-weight: bold; color: #111827;">{{ $orden->empresa->nombre ?? 'Desconocida' }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endsection
