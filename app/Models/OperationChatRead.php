<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationChatRead extends Model
{
    protected $fillable = [
        'operation_id',
        'user_id',
        'last_read_message_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Operation linked to this read marker.
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    /**
     * User linked to this read marker.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Last message marked as read.
     */
    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(OperationChatMessage::class, 'last_read_message_id');
    }
}

