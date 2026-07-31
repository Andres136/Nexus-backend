<?php

namespace App\Models\Crm;

use App\Models\Departamentos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChatbotConfiguracion extends Model
{
    protected $table = 'chatbot_configuraciones';
    protected $fillable = [
        'nombre',
        'avatar_url',
        'mensaje_bienvenida',
        'prompt_sistema',
        'sitio_web_url',
        'sitio_web_contexto',
        'sitio_web_actualizado_at',
        'departamento_id',
        'activo',
        'openai_model',
    ];
    protected $casts = [
        'activo' => 'boolean',
        'sitio_web_actualizado_at' => 'datetime',
    ];
    protected $hidden = ['sitio_web_contexto'];

    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }

    /**
     * `avatar_url` guarda una ruta de disco (subida como foto, igual que
     * La ruta del avatar del bot o, si se configuró manualmente, una URL absoluta.
     */
    public function avatarUrlCompleta(): ?string
    {
        if (!$this->avatar_url) {
            return null;
        }

        if (str_starts_with($this->avatar_url, 'http')) {
            return $this->avatar_url;
        }

        return Storage::disk('public')->url($this->avatar_url);
    }
}
