<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Model;

class CorporateDocumentDownload extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'corporate_document_id',
        'ip_address',
        'user_agent',
        'referer',
        'created_at',
    ];

    public function document()
    {
        return $this->belongsTo(CorporateDocument::class, 'corporate_document_id');
    }
}
