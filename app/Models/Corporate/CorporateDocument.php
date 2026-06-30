<?php

namespace App\Models\Corporate;

use Illuminate\Database\Eloquent\Model;

class CorporateDocument extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'description',
        'long_description',
        'icon',
        'file_type',
        'file_size',
        'pages',
        'last_update',
        'category',
        'theme',
        'file_path',
        'downloads_count',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_update' => 'date:Y-m-d',
        'downloads_count' => 'integer',
        'pages' => 'integer',
        'sort_order' => 'integer',
    ];

    public function features()
    {
        return $this->hasMany(CorporateDocumentFeature::class)->orderBy('sort_order');
    }

    public function benefits()
    {
        return $this->hasMany(CorporateDocumentBenefit::class)->orderBy('sort_order');
    }

    public function downloads()
    {
        return $this->hasMany(CorporateDocumentDownload::class);
    }
}
