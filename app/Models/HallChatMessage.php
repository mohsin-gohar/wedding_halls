<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HallChatMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'hall_id',
        'user_id',
        'ip_address',
        'session_id',
        'sender_type',
        'message_type',
        'message',
        'voice_file',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Get the hall this message belongs to.
     */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /**
     * Get the user who sent this message (if logged in).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope messages for a specific hall.
     */
    public function scopeForHall($query, string $hallId)
    {
        return $query->where('hall_id', $hallId);
    }

    /**
     * Scope messages by sender type.
     */
    public function scopeBySender($query, string $senderType)
    {
        return $query->where('sender_type', $senderType);
    }

    /**
     * Scope unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Get chat participants for a hall (distinct customers with their IP/session).
     * Used by hall owners to see which customers have messaged them.
     */
    public static function getChatParticipants(string $hallId): array
    {
        return self::where('hall_id', $hallId)
            ->where('sender_type', 'customer')
            ->select('ip_address', 'session_id')
            ->selectRaw('COUNT(*) as message_count, MAX(created_at) as last_message_at')
            ->groupBy('ip_address', 'session_id')
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn ($row) => [
                'ip_address' => $row->ip_address,
                'session_id' => $row->session_id,
                'message_count' => (int) $row->message_count,
                'last_message_at' => $row->last_message_at,
            ])
            ->toArray();
    }

    /**
     * Get conversation between a hall and a specific customer (matched by IP+session).
     */
    public static function getConversation(string $hallId, string $ipAddress, string $sessionId): array
    {
        return self::where('hall_id', $hallId)
            ->where('ip_address', $ipAddress)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'message_type' => $msg->message_type,
                'message' => $msg->message,
                'voice_file' => $msg->voice_file,
                'is_read' => (bool) $msg->is_read,
                'created_at' => $msg->created_at->format('Y-m-d H:i:s'),
                'user_name' => $msg->user?->name ?? ($msg->sender_type === 'hall_owner' ? 'Venue Owner' : 'You'),
            ])
            ->toArray();
    }

    /**
     * Mark all messages from a customer to a hall as read.
     */
    public static function markAsRead(string $hallId, string $ipAddress, string $sessionId): int
    {
        return self::where('hall_id', $hallId)
            ->where('ip_address', $ipAddress)
            ->where('session_id', $sessionId)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
