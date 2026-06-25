<?php

namespace App\Models\comunicaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialTickect extends Model
{
    use HasFactory;

    protected $table = 'tickets_historial';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'comentario',
        'soporte',
        'link',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
