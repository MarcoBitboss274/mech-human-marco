<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'revision_id',
        'content',
        'created_by',
    ];

    public function revision(): BelongsTo
    {
        return $this->belongsTo(Revision::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
