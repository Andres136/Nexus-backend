<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Model;

class CorporateDocumentFeature extends Model
{
    protected $fillable = [
        'corporate_document_id',
        'text',
        'sort_order',
    ];

    public function document()
    {
        return $this->belongsTo(CorporateDocument::class, 'corporate_document_id');
    }
}
