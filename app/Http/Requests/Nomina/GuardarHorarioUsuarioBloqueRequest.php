<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class GuardarHorarioUsuarioBloqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'bloques' => 'required|array|min:1',
            'bloques.*.dia_semana' => 'required|integer|min:1|max:7',
            'bloques.*.hora_inicio' => 'required|date_format:H:i',
            'bloques.*.hora_fin' => 'required|date_format:H:i',
            'bloques.*.orden' => 'nullable|integer|min:0',
            'bloques.*.status' => 'sometimes|boolean',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $bloques = collect($this->input('bloques', []))
                ->filter(fn ($bloque) => !empty($bloque['hora_inicio']) && !empty($bloque['hora_fin']));

            $porDia = $bloques->groupBy('dia_semana');

            foreach ($porDia as $dia => $bloquesDelDia) {
                $ordenados = $bloquesDelDia
                    ->map(fn ($bloque) => [
                        'inicio' => $this->minutosHora($bloque['hora_inicio']),
                        'fin' => $this->minutosHora($bloque['hora_fin']),
                    ])
                    ->sortBy('inicio')
                    ->values();

                foreach ($ordenados as $index => $bloque) {
                    if ($bloque['fin'] <= $bloque['inicio']) {
                        $validator->errors()->add(
                            "bloques.dia_{$dia}",
                            "La hora de fin debe ser posterior a la hora de inicio en el día {$dia}."
                        );
                    }

                    $siguiente = $ordenados->get($index + 1);
                    if ($siguiente && $siguiente['inicio'] < $bloque['fin']) {
                        $validator->errors()->add(
                            "bloques.dia_{$dia}",
                            "Hay bloques que se solapan en el día {$dia}."
                        );
                    }
                }
            }
        });
    }

    private function minutosHora(string $hora): int
    {
        [$horas, $minutos] = array_map('intval', explode(':', $hora));

        return ($horas * 60) + $minutos;
    }
}
