<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Share extends Model
{    protected $fillable = [
        'pdf_id',
        'collection_id',
        'idProfile',
        'permissions'
    ];

    /**
     * Get the collection that is shared.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Get the PDF that is shared.
     */
    public function pdf(): BelongsTo
    {
        return $this->belongsTo(Pdf::class);
    }

    /**
     * Get the user this PDF is shared with.
     */    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idProfile', 'id_profile');
    }
}
