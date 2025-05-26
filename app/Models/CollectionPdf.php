<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CollectionPdf extends Pivot
{
    protected $table = 'collection_pdfs';

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function pdf()
    {
        return $this->belongsTo(Pdf::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by', 'id_profile');
    }
}
