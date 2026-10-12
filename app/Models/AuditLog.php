<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Tulis entri audit log (dipakai oleh AuditLogService).
     */
    public static function record(
        ?int $actorUserId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $metadata = [],
        ?string $ipAddress = null,
    ): self {
        return static::create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata === [] ? null : $metadata,
            'ip_address' => $ipAddress,
        ]);
    }
}
