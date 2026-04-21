<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationChatMessage extends Model
{
    protected $fillable = [
        'operation_id',
        'user_id',
        'body',
    ];

    /**
     * Operation linked to this message.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    /**
     * Author linked to this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

